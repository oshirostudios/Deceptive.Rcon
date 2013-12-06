using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Diagnostics;
using System.Linq;
using System.ServiceProcess;
using System.Text;

namespace Deceptive.Rcon.Service
{
    public partial class RconService : ServiceBase
    {
        public RconService()
        {
            ServiceName = "Deceptive Rcon Service";
            EventLog.Log = "Application";

            CanHandlePowerEvent = true;
            CanHandleSessionChangeEvent = true;
            CanPauseAndContinue = true;
            CanShutdown = true;
            CanStop = true;

            InitializeComponent();
        }

        protected override void OnStart(string[] args)
        {
        }

        protected override void OnStop()
        {
        }
    }
}
