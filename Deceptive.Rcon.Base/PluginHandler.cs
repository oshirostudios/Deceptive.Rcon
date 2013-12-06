using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Reflection;
using System.Text;

namespace Deceptive.Rcon.Base
{
    public class PluginHandler
    {
        public const int PriorityLast = 0;
        public const int PriorityLowest = 1;
        public const int PriorityLow = 2;
        public const int PriorityNormal = 3;
        public const int PriorityHigh = 4;
        public const int PriorityHighest = 5;

        private Dictionary<string, IPlugin> _plugins;

        private static PluginHandler _instance;
        public static PluginHandler Instance
        {
            get
            {
                if (_instance == null)
                    _instance = new PluginHandler();

                return _instance;
            }
        }

        private AppDomain _current;

        private PluginHandler()
        {
            _plugins = new Dictionary<string, IPlugin>();
        }

        public bool LoadPlugins(string folder)
        {
            var loaded = false;

            if (!Directory.Exists(folder))
            {
                Directory.CreateDirectory(folder);
                return false;
            }

            var files = Directory.GetFiles(folder, "*.dll");

            foreach (string file in files)
            {
                if (_plugins.ContainsKey(file))
                    continue;

                if (!loaded)
                    Controller.Log("Running plugins...");

                loaded = true;

                try
                {
                    var assembly = Assembly.LoadFile(file);

                    if (assembly != null)
                    {
                        var types = assembly.GetTypes();

                        foreach (var type in types)
                        {
                            if (typeof (IPlugin).IsAssignableFrom(type))
                            {
                                var o = Activator.CreateInstance(type);
                                var plugin = o as IPlugin;

                                plugin.Load();

                                _plugins.Add(file, plugin);
                            }
                        }
                    }
                }
                catch (Exception e)
                {
                    Debug.WriteLine(e.Message);
                }
            }

            return true;
        }

        public void Run(Server server, bool firstRun)
        {
            Controller.Log("Running plugins...");

            for (var priority = PriorityHighest; priority >= PriorityLast; priority--)
            {
                foreach (var plugin in _plugins.Values)
                {
                    if (plugin.Priority() == priority)
                        plugin.Run(server, firstRun);

                    if (priority == PriorityLast)
                        break;
                }
            }
        }
    }
}
