using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Reflection;
using System.Runtime.InteropServices;
using System.Text;
using System.Threading;
using System.Windows.Forms;
using Deceptive.Rcon.Base.Extensions;
using Deceptive.Rcon.Base.Games;

namespace Deceptive.Rcon.Base
{
    public class Server
    {
        private int _id;
        private int _type;
        private string _name;
        private string _description;

        private string _ip;
        private int _port;
        private string _rconPassword;
        private string _logUrl;

        private bool _ranked;
        private int _warnings;
        private bool _showRestrictions;
        private int _maxPing;

        private bool _running;

        private Game _game;
        private List<LogData> _logFile;
        private Dictionary<int, Player> _slots;

        public Server(int serverId)
        {
            var connection = Database.Create();

            var query = string.Format("SELECT * FROM rcon_servers WHERE server_id = {0}", serverId);
            var response = Database.Query(connection, query);

            while (response.Read())
            {
                _id = serverId;
                _name = response.GetStringOrNull("server_name");
                _description = response.GetStringOrNull("server_description");
                _type = response.GetInt32("server_type_id");

                _ip = response.GetStringOrNull("server_ip");
                _port = response.GetInt32("server_port");
                _rconPassword = response.GetStringOrNull("server_rcon_password");
                _logUrl = response.GetStringOrNull("server_log_url");

                _ranked = response.GetBoolean("server_ranked");
                _warnings = response.GetInt32("server_warnings");
                _showRestrictions = response.GetBoolean("server_show_restrictions");
                _maxPing = response.GetInt32("server_max_ping");
            }

            response.Close();
            Database.Close(connection);

            switch (_type)
            {
                case 1:
                {
                    _game = new BlackOpsGame();
                    _game.Setup(_ip, _port, _rconPassword);
                } break;
            }

            _logFile = new List<LogData>();
            _slots = new Dictionary<int, Player>();
        }

        public int Id { get { return _id; } }
        public int Type { get { return _type; } }
        public string Name { get { return _name; } }
        public string Description { get { return _description; } }
        public string IP { get { return _ip; } }
        public int Port { get { return _port; } }
        public string RconPassword { get { return _rconPassword; } }
        public string LogUrl { get { return _logUrl; } }
        public bool Ranked { get { return _ranked; } }
        public int Warnings { get { return _warnings; } }
        public bool ShowRestrictions { get { return _showRestrictions; } }
        public int MaxPing { get { return _maxPing; } }

        public void Start()
        {
            _running = true;

            var firstRun = true;

            while (_running)
            {
                Update();
                Run(firstRun);

                Thread.Sleep(1000);

                firstRun = false;
            }
        }

        public void Stop()
        {
            _running = false;
        }

        private void Update()
        {
            PluginHandler.Instance.LoadPlugins(string.Format("{0}\\modules", Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location)));
        }

        private void Run(bool firstRun)
        {
            PluginHandler.Instance.Run(this, firstRun);
            _logFile.Clear();
        }

        public void Tell(int slot, string message)
        {
            Command(string.Format("tell {0} {1}", slot, message), false);
        }

        public void Say(string message)
        {
            Command(string.Format("say {0}", message), false);
        }

        public string Command(string command, bool response = true)
        {
            return _game.Command(command, response);
        }

        public void UpdateSlot(int slot, string guid, string name, int score, string ip)
        {
            if (_slots.ContainsKey(slot))
            {
                if (_slots[slot].Guid == guid)
                {
                    _slots[slot].Update(name, score, ip);
                    return;
                }

                _slots.Remove(slot);
            }

            var slots = _slots.Where(player => player.Value.Guid == guid).ToList();

            foreach (var value in slots)
            {
                _slots.Remove(value.Key);
            }

            _slots.Add(slot, new Player(guid, this));
            _slots[slot].Update(name, score, ip);
        }

        public void LogData(double timestamp, char command, string message)
        {
            _logFile.Add(new LogData
            {
                Timestamp = timestamp,
                Command = command,
                Message = message
            });
        }

        public List<LogData> ProcessLog(char command = '-')
        {
            return command == '-' ? _logFile.ToList() : _logFile.Where(logData => logData.Command == command).ToList();
        }

        public void RemoveLog(LogData log)
        {
            if (_logFile.Contains(log))
                _logFile.Remove(log);
        }

        public Player GetPlayer(string guid)
        {
            return _slots.Values.FirstOrDefault(player => player.Guid == guid);
        }

        public int GetGameID(string game)
        {
            var gameId = -1;

            var connection = Database.Create();
            var query = string.Format("SELECT game_id FROM rcon_game WHERE game_value = '{0}'", game);

            var response = Database.Query(connection, query);

            if (response.Read())
                gameId = response.GetInt32("game_id");

            response.Close();
            Database.Close(connection);

            return gameId;
        }

        public int GetMapID(string map)
        {
            var mapId = -1;

            var connection = Database.Create();
            var query = string.Format(map.StartsWith("mp_") ? "SELECT map_id FROM rcon_maps WHERE map_file = '{0}'" : "SELECT map_id FROM rcon_maps WHERE map_name = '{0}%'", map);

            var response = Database.Query(connection, query);

            if (response.Read())
                mapId = response.GetInt32("map_id");

            response.Close();
            Database.Close(connection);

            return mapId;
        }

        public void SetServerStatus(int statusId, string mapName, int playlist, int rotation, string gametype = "tdm")
        {
            var query = string.Format("DELETE FROM rcon_server_status WHERE server_id = {0} AND server_status_id = {1}", Id, statusId);

            var connection = Database.Create();
            Database.Execute(connection, query);

            query = string.Format("INSERT INTO rcon_server_status (server_id, server_status_id, map_id, mode_type_id, game_id, rotation_sort) VALUES ({0}, {1}, {2}, {3}, {4}, {5})", Id, statusId, GetMapID(mapName), playlist, GetGameID(gametype), rotation);
            Database.Execute(connection, query);
            Database.Close(connection);
        }
    }
}
