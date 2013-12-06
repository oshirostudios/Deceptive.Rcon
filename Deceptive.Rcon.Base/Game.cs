using System;
using System.Linq;
using System.Net;
using System.Net.Sockets;
using System.Text;
using System.Threading;

namespace Deceptive.Rcon.Base
{
    public abstract class Game
    {
        protected string Host;
        protected int Port;
        protected string Password;
        protected byte[] Format;
        protected string Message;

        private IPEndPoint _targetEndPoint;

        public void Setup(string host, int port, string password)
        {
            Host = host;
            Port = port;
            Password = password;

            _targetEndPoint = new IPEndPoint(IPAddress.Parse(Host), Port);
        }

        public string Command(string command, bool response = true)
        {
            var socket = new UdpClient();
            var message = "";

            for (var count = 0; count < 3 && message == ""; count++)
            {
                if (Send(socket, command))
                    message = Receive(socket);

                if (message == "")
                    Thread.Sleep(50);
            }

            socket.Close();

            if (response)
                return message;
            
            return message.Length > 0 ? "1" : "0";
        }

        public bool Send(UdpClient client, string command)
        {
            var message = Message.Replace("PWD", Password).Replace("CMD", command);
            var bytes = Encoding.ASCII.GetBytes(message);
            var dgram = new byte[bytes.Length + Format.Length];

            for (var count = 0; count < dgram.Length; count++)
            {
                if (count < Format.Length)
                    dgram[count] = Format[count];
                else
                    dgram[count] = bytes[count - Format.Length];
            }

            return client.Send(dgram, dgram.Length, _targetEndPoint) == dgram.Length;
        }

        public string Receive(UdpClient client)
        {
            Thread.Sleep(200);

            var available = client.Available;

            if (available > 0)
            {
                var bytes = client.Receive(ref _targetEndPoint);
                var data = Encoding.ASCII.GetString(bytes);

                if (available > 1200)
                {
                    bytes = client.Receive(ref _targetEndPoint);
                    data = string.Concat(data, Encoding.ASCII.GetString(bytes));
                }

                return data.Replace("\n\n", string.Empty).Replace("????print\n", string.Empty).Replace("print", string.Empty);
            }

            return "";
        }
    }
}
