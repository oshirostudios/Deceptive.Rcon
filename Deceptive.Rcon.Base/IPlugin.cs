using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace Deceptive.Rcon.Base
{
    public interface IPlugin
    {
        void Load();
        void Run(Server server, bool firstRun = false);
        bool State();
        int Priority();
    }
}
