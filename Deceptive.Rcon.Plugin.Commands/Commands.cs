using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using Deceptive.Rcon.Base;
using Deceptive.Rcon.Base.Extensions;

namespace Deceptive.Rcon.Plugin.Commands
{
    public class Commands : IPlugin
    {
        private Dictionary<string, string> _commandKeys;

        private bool _restart;

        public void Load()
        {
            Controller.Log(" - loading 'Commands' plugin");

            _commandKeys = new Dictionary<string, string>
            {
                { "&Game", "Game" },
                { "&Map", "Map" },
                { "&Mode", "Mode" },
                { "&Player", "Player" },
                { "&Players", "Players" },
                { "&Playlist", "Playlist" },
                { "&Type", "Type" }
            };

            _restart = false;
        }

        public void Run(Server server, bool firstRun = false)
        {
            if (firstRun) 
                return;

            Controller.Log(" - running 'Commands' plugin");

            // select all of the chat messages that start with !
            var commands = ProcessMessages(server.ProcessLog().Where(m => (m.Command == 'P' || m.Command == 'G')).ToList());

            if (commands.Count <= 0) 
                return;

            Controller.Log("   - processing {0} commands", commands.Count);

            foreach (var command in commands)
            {
                if (ProcessCommand(server, command))
                    server.RemoveLog(command.Log);
            }
        }

        public bool State()
        {
            return _restart;
        }

        public int Priority()
        {
            return PluginHandler.PriorityNormal;
        }

        private List<CommandMessage> ProcessMessages(List<LogData> log)
        {
            var commands = new List<CommandMessage>();

            foreach (var logData in log)
            {
                var data = logData.Message.Split(new [] { ';' }, 4);

                if (data[3][1] == '!')
                {
                    commands.Add(new CommandMessage
                    {
                        Guid = data[0],
                        Slot = int.Parse(data[1]),
                        Name = data[2],
                        Message = data[3].Substring(1),
                        Log = logData
                    });
                }
            }

            return commands;
    	}

        private bool ProcessCommand(Server server, CommandMessage message)
        {
            var messageData = message.Message.Split(' ');

            var command = messageData[0].Substring(1);
            var commandData = GetCommand(server.Id, command);

            if (commandData != null)
            {
                if (commandData.Name == "help")
                {
                    return true;
                }
                
                if (commandData.Name == "claimserver")
                {
                    return true;
                }

                if (!HasAccess(server, message.Guid, commandData.Name))
                {
                    server.Tell(message.Slot, string.Format("You do not have access to ^1'!{0}'", commandData.Name));
                    return true;
                }

                if (commandData.Name == "restart")
                {
                    server.Tell(message.Slot, "Deceptive {Rcon} will restart soon");
                    _restart = true;

                    return true;
                }
            }

            return false;
        }

        private bool HasAccess(Server server, string guid, string command)
        {
            if (guid == "CONSOLE")
                return true;

            var access = false;
            var connection = Database.Create();

            var player = server.GetPlayer(guid);

            if (player == null)
                return false;

            var query = string.Format("SELECT * FROM rcon_commands INNER JOIN rcon_server_commands ON rcon_commands.command_id = rcon_server_commands.command_id WHERE rcon_server_commands.server_id = {0} AND group_id = {1} AND command_name = '{2}'", server.Id, player.GroupId, command);
            var results = Database.Query(connection, query);

            if (results.HasRows && results.Read())
                access = (results.GetInt32("server_command_access") == 1);

            Database.Close(connection);

            return access;
        }

        private CommandData GetCommand(int serverId, string command, bool byName = true)
        {
            CommandData returnValue = null;

            var connection = Database.Create();

            var query = string.Format(byName ? "SELECT * FROM rcon_commands WHERE (server_id = {0} OR server_id = 0) AND command_name = '{1}'" : "SELECT * FROM rcon_commands WHERE (server_id = {0} OR server_id = 0) AND command_id = {1}", serverId, command);
            var response = Database.Query(connection, query);

            if (response.HasRows)
            {
                response.Read();

                returnValue = new CommandData
                {
                    Id = response.GetInt32("command_id"),
                    Name = response.GetStringOrNull("command_name"),
                    Format = response.GetStringOrNull("command_format"),
                    Commands = response.GetStringOrNull("command_rcon_command"),
                    Response = response.GetStringOrNull("command_response"),
                    SQL = response.GetStringOrNull("command_sql_command")
                };
            }
            else if (byName)
            {
                response.Close();
                response = Database.Query(connection, string.Format("SELECT * FROM rcon_command_alias WHERE command_alias = '{0}'", command));

                if (response.HasRows)
                {
                    response.Read();
                    returnValue = GetCommand(serverId, response.GetString("command_id"), false);
                }
            }

            response.Close();
            Database.Close(connection);

            return returnValue;
        }

        private class ValueData
        {
            public int Id;
            public string Value;
            public string Name;
        }

        private ValueData GetData(string request, string value)
        {
            switch (request)
            {
                case "&Mode":
                {
                    //("SELECT * FROM rcon_modes WHERE mode_shortcode = '{0}' OR mode_longcode = '{0}'", value);
                } break;

    			case "&Type":
                {
                    //("SELECT * FROM rcon_types WHERE type_shortcode = '{0}'", value);
                } break;
            }
        }

        private bool ClaimServer(CommandMessage message, string code)
        {
            if (message.Slot <= 0)
                return false;

            //var query = "SELECT * FROM rcon_servers";

            return false;
        }

        private class CommandMessage
        {
            public string Guid;
            public int Slot;
            public string Name;
            public string Message;
            public LogData Log;
        }

        private class CommandData
        {
            public int Id;
            public string Name;
            public string Format;
            public string Commands;
            public string Response;
            public string SQL;
        }
    }
}
