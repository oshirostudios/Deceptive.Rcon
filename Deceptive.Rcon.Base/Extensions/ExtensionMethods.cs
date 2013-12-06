using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using MySql.Data.MySqlClient;

namespace Deceptive.Rcon.Base.Extensions
{
    public static class ExtensionMethods
    {
        public static double ToUnixTimestamp(this DateTime that)
        {
            var epoch = new DateTime(1970, 1, 1, 0, 0, 0, 0);
            var span = (that - epoch);

            return span.TotalSeconds;
        }

        public static DateTime ToDateTime(this double that)
        {
            var dateTime = new DateTime(1970, 1, 1, 0, 0, 0, 0);
            return dateTime.AddSeconds(that);
        }

        public static string GetStringOrNull(this MySqlDataReader that, string column)
        {
            return that.GetStringOrNull(that.GetOrdinal(column));
        }

        public static string GetStringOrNull(this MySqlDataReader that, int column)
        {
            if (that.IsDBNull(column))
                return "";

            return that.GetString(column);
        }
    }
}
