namespace Deceptive.Rcon.Base.Games
{
    public class BlackOpsGame : Game
    {
        public BlackOpsGame()
        {
            Format = new byte[5];
            Format[0] = byte.Parse("255");
            Format[1] = byte.Parse("255");
            Format[2] = byte.Parse("255");
            Format[3] = byte.Parse("255");
            Format[4] = byte.Parse("00");

            Message = "PWD CMD";
        }
    }
}
