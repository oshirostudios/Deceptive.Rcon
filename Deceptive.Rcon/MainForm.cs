using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Windows.Forms;
using Deceptive.Rcon.Base;

namespace Deceptive.Rcon
{
    public partial class MainForm : Form
    {
        public MainForm()
        {
            InitializeComponent();
        }

        private void MainForm_Load(object sender, EventArgs e)
        {
            Controller.Start(list_Log);
            Controller.Update();

            timer_Monitor.Enabled = true;
        }

        private void timer_Monitor_Tick(object sender, EventArgs e)
        {
            timer_Monitor.Enabled = false;
            //Controller.Update();
            timer_Monitor.Enabled = true;
        }

        private void MainForm_FormClosing(object sender, FormClosingEventArgs e)
        {
            Controller.Shutdown();
        }
    }
}
