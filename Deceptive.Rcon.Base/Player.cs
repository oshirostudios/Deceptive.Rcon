using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using Deceptive.Rcon.Base.Extensions;

namespace Deceptive.Rcon.Base
{
    public class Player
    {
        public const int ActionNone = 0;
        public const int ActionJoin = 1;
        public const int ActionQuit = 2;
        public const int ActionName = 3;
        public const int ActionLog = 4;
        public const int ActionBan = 5;
        public const int ActionKick = 6;
        public const int ActionWarn = 7;

        public int Id { get; private set; }

        public string Guid { get; private set; }
        public string Name { get; private set; }

        public string IpAddress { get; private set; }
        public string Ping { get; private set; }

        public Server Server { get; private set; }
        public int Slot { get; private set; }
        public Statistics Statistics { get; private set; }

        public int GroupId;

        public Player(string guid, Server server)
        {
            var connection = Database.Create();

            Id = GetPlayer(connection, guid);
            Guid = guid;

            var query = string.Format("SELECT * FROM rcon_players WHERE player_id = {0}", Id);
            var result = Database.Query(connection, query);

            Name = result.GetStringOrNull("player_name");
            IpAddress = result.GetStringOrNull("player_last_ip");

            result.Close();

            Server = server;
            GroupId = GetGroup(connection);

            Database.Close(connection);
        }

        public void Update(string name, int score, string ip)
        {
            var connection = Database.Create();

            if (Name != name)
                UpdateName(connection, name);

            if (IpAddress != ip)
                UpdateLastIp(connection, ip);

            Statistics.Update(connection, score);

            Database.Close(connection);
        }

        private void UpdateName(int connection, string name)
        {
            var oldName = Name;
            Name = name;

            Database.Execute(connection, string.Format("UPDATE FROM rcon_players SET player_name = '{0}' WHERE player_id = {1}", name, Id));

            if (oldName.Length > 0 && Name != oldName)
                PerformAction(connection, ActionName, "Name Changed", string.Format("{0} to {1}", oldName, Name), DateTime.UtcNow.ToString("yyyy-MM-dd HH:mm:ss.000000"));
        }

        private void UpdateLastIp(int connection, string ip)
        {
            IpAddress = ip;

            Database.Execute(connection, string.Format("UPDATE FROM rcon_players SET player_last_ip = '{0}' WHERE player_id = {1}", ip, Id));
        }

        private void PerformAction(int connection, int action, string reason, string text, string timestamp)
        {
            
        }

        private int GetGroup(int connection)
        {
            var groupId = -1;

		    var query = string.Format("SELECT * FROM rcon_player_groups WHERE player_id = {0} AND server_id = {1}", Id, Server.Id);
		    var result = Database.Query(connection, query);
		
		    if (result.HasRows && result.Read())
			    groupId = result.GetInt32("group_id");

            result.Close();

            if (groupId == -1)
            {
                Database.Execute(connection, string.Format("INSERT INTO rcon_player_groups (player_id, server_id, group_id) VALUES ({0}, {1}, 6)", Id, Server.Id));
                groupId = 6;
            }

		    return groupId;
	    }

        private int GetPlayer(int connection, string guid)
        {
            var playerId = -1;

            var query = string.Format("SELECT player_id FROM rcon_players WHERE player_guid = '{0}'", guid);
            var result = Database.Query(connection, query);

            if (result.HasRows && result.Read())
                playerId = result.GetInt32("player_id");

            result.Close();

            if (playerId == -1)
                Database.Execute(connection, string.Format("INSERT INTO rcon_players (player_guid, player_name, player_last_ip) VALUES ('{0}', '', '')", guid));

            return (playerId == -1) ? GetPlayer(connection, guid) : playerId;
        }
    }

    public class Statistics
    {
        public string Score { get; private set; }

        public void Update(int connection, int score)
        {
            throw new NotImplementedException();
        }
    }
}
