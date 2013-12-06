using System;
using System.Collections.Generic;
using System.Data;
using System.Linq;
using System.Text;
using MySql.Data.MySqlClient;

namespace Deceptive.Rcon.Base
{
    public static class Database
    {
        private static Dictionary<int, MySqlConnection> _pool;
        private static Dictionary<int, string> _cached;

        private static string _server;
        private static int _port;
        private static string _user;
        private static string _password;
        private static string _database;
        private static string _prefix;

        static Database()
        {
            _pool = new Dictionary<int, MySqlConnection>();
            _cached = new Dictionary<int, string>();
        }

        public static void Initialise(string server, int port, string user, string password, string database, string prefix = "")
        {
            _server = server;
            _port = port;
            _user = user;
            _password = password;
            _database = database;
            _prefix = prefix;
        }

        private static MySqlConnection Connect()
        {
            try
            {
                var connectionString = string.Format("server={0};port={1};userid={2};password={3};database={4};usecompression=true",
                    _server, _port, _user, _password, _database);
                var connection = new MySqlConnection(connectionString);

                connection.Open();

                return connection;
            }
            catch (MySqlException e)
            {
                Controller.Log("Error {0}", e.ToString());
            }

            return null;
        }

        public static int Create()
        {
            var connection = Connect();

            if (connection == null)
                return -1;

            var found = false;
            var counter = 0;

            while (!found)
            {
                counter++;

                if (!_pool.ContainsKey(counter))
                {
                    _pool.Add(counter, connection);
                    found = true;
                }
            }

            return counter;
        }

        public static void Close(int connection)
        {
            if (_cached.ContainsKey(connection))
            {
                (new MySqlCommand(_cached[connection], _pool[connection])).ExecuteNonQuery();
                _cached.Remove(connection);
            }

            if (!_pool.ContainsKey(connection))
                return;

            _pool[connection].Close();
            _pool.Remove(connection);
        }

        public static void Execute(int connection, string query)
        {
            if (!_pool.ContainsKey(connection))
                return;

            if (!_cached.ContainsKey(connection))
                _cached.Add(connection, query);
            else
                _cached[connection] += "; " + query;
        }

        public static MySqlDataReader Query(int connection, string query)
        {
            if (!_pool.ContainsKey(connection))
                return null;

            var command = new MySqlCommand(query, _pool[connection]);
            return command.ExecuteReader();
        }
    }
}
