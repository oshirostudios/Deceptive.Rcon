using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading;
using System.Windows.Forms;

namespace Deceptive.Rcon.Base
{
    public static class Controller
    {
        private static Dictionary<int, Thread> _processes;
        private static ListBox _log;

        public static void Start(ListBox log = null)
        {
            _log = log;
            _processes = new Dictionary<int, Thread>();
            Database.Initialise("deceptivestudios.com", 3306, "rcon_deceptive", "rcondb", "rcon_deceptivestudios", "rcon_");
        }

        public static void Shutdown()
        {
            foreach (var process in _processes.Values)
            {
                process.Abort();
                process.Join();
            }

            _processes.Clear();
        }

        public static bool Update()
        {
            var stoppedProcesses = _processes.Keys.Where(key => !IsRunning(key)).ToList();

            foreach (var key in stoppedProcesses)
                _processes.Remove(key);

            var connection = Database.Create();

            var query = "SELECT server_id FROM rcon_servers WHERE LENGTH(IFNULL(server_ip, '')) > 0 AND LENGTH(IFNULL(server_port, '')) > 0 AND LENGTH(IFNULL(server_log_url, '')) > 0 AND LENGTH(IFNULL(server_rcon_password, '')) > 0 AND server_monitor = 2";
            var response = Database.Query(connection, query);

            while (response.Read())
            {
                var serverId = response.GetInt32("server_id");

                if (!_processes.ContainsKey(serverId))
                    Start(serverId);
            }

            response.Close();

            return true;
        }

        private delegate void LogDelegate(string format, params object[] data);
        public static void Log(string format, params object[] data)
        {
            if (_log == null)
                return;

            if (_log.InvokeRequired)
            {
                _log.BeginInvoke(new LogDelegate(Log), format, data);
            }
            else
            {
                _log.Items.Add(string.Format("[{0}] {1}", DateTime.Now.ToString("yyyy-MM-dd H:mm:ss"), string.Format(format, data)));

                if (_log.Items.Count > 100)
                    _log.Items.RemoveAt(0);

                _log.SelectedIndex = _log.Items.Count - 1;
            }
        }

        private static void Start(int serverId)
        {
            var server = new Server(serverId);
            var thread = new Thread(server.Start);

            _processes.Add(serverId, thread);

            thread.Start();
        }

        private static bool IsRunning(int serverId)
        {
            if (!_processes.ContainsKey(serverId))
                return false;

            var success = false;

            var connection = Database.Create();

            var query = string.Format("SELECT UNIX_TIMESTAMP(CURRENT_TIMESTAMP) AS time_now, UNIX_TIMESTAMP(IFNULL(server_last_run, CURRENT_TIMESTAMP)) AS time_update FROM rcon_servers WHERE server_id = {0}", serverId);
            var response = Database.Query(connection, query);

            while (response.Read())
            {
                var timeNow = response.GetString("time_now");
                var timeUpdate = response.GetString("time_update");

                if ((long.Parse(timeNow) - long.Parse(timeUpdate)) < 120)
                    success = true;
            }

            response.Close();
            Database.Close(connection);

            if (!success)
            {
                _processes[serverId].Abort();
                _processes[serverId].Join();

                _processes.Remove(serverId);
            }

            return success;
        }
    }
}
