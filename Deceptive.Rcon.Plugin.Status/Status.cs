using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.IO.Pipes;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using Deceptive.Rcon.Base;
using Deceptive.Rcon.Base.Extensions;
using MySql.Data.MySqlClient;

namespace Deceptive.Rcon.Plugin.Status
{
    public class Status : IPlugin
    {
        private double _lastUpdate = 0;
        private int _connection;

        private string _lastInitString;
        private Dictionary<string, string> _serverState;

        public void Load()
        {
            Controller.Log(" - loading 'Status' plugin");
            _lastInitString = null;
        }

        public void Run(Server server, bool firstRun = false)
        {
            if (firstRun) 
                return;

            Controller.Log(" - running 'Status' plugin");

            var state = server.Command(server.Type == 1 ? "teamstatus" : "status");

            if (state.Length > 30)
            {
                var players = Process(server, state);
                Controller.Log("   - server {0} has {1} players", server.Id, players);
            }

            var initStrings = server.ProcessLog('I');

            if (initStrings.Count > 0)
                _lastInitString = initStrings.Last().Message;

            var now = DateTime.UtcNow.ToUnixTimestamp();

            if (!string.IsNullOrEmpty(_lastInitString) && (now - _lastUpdate) > 20)
            {
                _lastUpdate = now;
                _serverState = new Dictionary<string, string>();

                var values = _lastInitString.Split('\\');

                for (var count = 0; count < values.Length; count += 2)
                {
                    _serverState.Add(values[count], values[count + 1]);
                }

                if (_serverState["playlist_enabled"][0] == '1')
                    server.SetServerStatus(1, _serverState["mapname"], int.Parse(_serverState["playlist"]), 0);
                else
                    server.SetServerStatus(1, _serverState["mapname"], -1, 0, _serverState["g_gametype"]);
            }
        }

        public bool State()
        {
            return true;
        }

        public int Priority()
        {
            return PluginHandler.PriorityLowest;
        }

        private int Process(Server server, string state)
        {
            var players = 0;
            var rows = state.Split('\n');

            _connection = Database.Create();

            DeletePlayers(server.Id);

            foreach (var row in rows)
            {
                try
                {
                    if (row.Length > 10 && row.Trim() != "" && !row.Contains(" ZMBI ") && (!row.Contains("qport") && !row.Contains("lastmsg")) &&
                        (!row.Contains("????") && !row.Contains("print") &&
                        (!row.Contains("--- ----- ----") && !row.Contains("map: "))) && !row.Contains("democlient^7"))
                    {
                        var values = SplitRow(row);

                        if (values.Count >= 8)
                        {
                            if (values[7].Contains(":"))
                                values[7] = values[7].Substring(0, values[7].IndexOf(':'));

                            var slot = int.Parse(values[0]);

                            if (slot > 0 && slot <= 32)
                            {
                                var ping = 0;

                                if (!int.TryParse(values[2], out ping))
                                    ping = 999;

                                UpdatePlayer(server.Id, slot, values[3], values[4], int.Parse(values[1]), values[7], ping);
                                server.UpdateSlot(slot, values[3], values[4], int.Parse(values[1]), values[7]);

                                players++;
                            }
                        }
                    }
                }
                catch (Exception e)
                {

                }
            }

            Database.Close(_connection);

            return players;
        }

        private void UpdatePlayer(int serverId, int slot, string guid, string name, int score, string ip, int ping)
        {
            name = Regex.Replace(name, @"[\000\010\011\012\015\032\042\047\134\140]", "\\$0").Replace(" ", "");

            Database.Execute(_connection, string.Format("INSERT INTO rcon_server_players (server_id, server_slot, server_player_guid, server_player_name, server_player_score, server_player_ip, server_player_ping) VALUES ({0}, {1}, '{2}', '{3}', {4}, '{5}', {6})",
                serverId, slot, guid, name, score, ip, ping));
        }

        private void DeletePlayers(int serverId)
        {
            Database.Execute(_connection, string.Format("DELETE FROM rcon_server_players WHERE server_id = {0}", serverId));
        }

        private List<string> SplitRow(string row)
        {
            row = row.Trim();

            while (row.Contains("  "))
            {
                row = row.Replace("  ", " ");
            }

            var array1 = row.Split(new [] { "^7 " }, 2, StringSplitOptions.None);
            var array2 = array1[0].Split(new[] {' '}, 5, StringSplitOptions.None);
            var array3 = array1[1].Split(' ');

            var values = new List<string>
            {
                array2[0], // slot
                array2[1], // score
                array2[2], // ping
                array2[3], // guid
                array2[4], // name

                array3[0], // team
                array3[1], // lastmsg
                array3[2], // ip
                array3[3], // qport
                array3[4] // rate
            };

            return values;
        }
    }
}
