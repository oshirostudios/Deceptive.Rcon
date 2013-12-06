-- MySQL dump 10.13  Distrib 5.1.52, for redhat-linux-gnu (i686)
--
-- Host: localhost    Database: rcon_deceptivestudios
-- ------------------------------------------------------
-- Server version	5.1.52

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `rcon_command_alias`
--

DROP TABLE IF EXISTS `rcon_command_alias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_command_alias` (
  `command_alias` varchar(20) NOT NULL,
  `command_id` int(11) NOT NULL,
  PRIMARY KEY (`command_alias`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rcon_command_alias`
--

LOCK TABLES `rcon_command_alias` WRITE;
/*!40000 ALTER TABLE `rcon_command_alias` DISABLE KEYS */;
INSERT INTO `rcon_command_alias` VALUES ('grank',8),('global',8),('gstats',9),('top',10),('stats',10),('kdr',11),('topdeath',12),('deaths',12),('death',12),('darwin',13),('topsuicide',13),('suicide',13),('suicides',13),('next',15),('pl',20),('cmd',22),('captures',17),('caps',17),('topcaps',17),('shame',26),('topshame',26),('wall',26),('sh',38),('abbreviation',38),('resetstats',37);
/*!40000 ALTER TABLE `rcon_command_alias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rcon_commands`
--

DROP TABLE IF EXISTS `rcon_commands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_commands` (
  `command_id` int(11) NOT NULL AUTO_INCREMENT,
  `server_type_id` int(11) NOT NULL,
  `command_name` varchar(20) NOT NULL,
  `command_format` varchar(100) NOT NULL,
  `command_rcon_command` varchar(500) NOT NULL,
  `command_response` varchar(100) DEFAULT NULL,
  `command_visible` int(11) NOT NULL DEFAULT '1',
  `command_sql_command` varchar(500) DEFAULT NULL,
  `command_unranked` tinyint(4) DEFAULT '0',
  `server_id` int(11) DEFAULT '0',
  PRIMARY KEY (`command_id`)
) ENGINE=MyISAM AUTO_INCREMENT=56 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rcon_commands`
--

LOCK TABLES `rcon_commands` WRITE;
/*!40000 ALTER TABLE `rcon_commands` DISABLE KEYS */;
INSERT INTO `rcon_commands` VALUES (1,1,'setmap','setmap &Map','setadmindvar sv_mapRotation &Map|setadmindvar playlist_excludeMap &!Map','tell &Sender ^1pm: ^7Success|say Next map will be ^1&Map',1,NULL,0,0),(2,1,'setmode','setmode &Mode &Type &Players','setadmindvar playlist &Playlist|setadmindvar playlist_enabled 1','tell &Sender ^1pm: ^7Success|say Next map will be &Mode &Type (&Playlist)',1,NULL,0,0),(3,1,'dlc','dlc &Value1 &Value2 &Value3','setadmindvar playlist_excludeDlc2 &Value1|setadmindvar playlist_excludeDlc3 &Value2|setadmindvar playlist_excludeDlc4 &Value3',NULL,0,NULL,0,0),(4,1,'kick','kick &Player','clientkick &Player &Final','tell &Sender ^1pm: ^7Success|say ^1&Player ^7kicked for ^1&Final',1,'INSERT INTO rcon_player_history (player_id, server_id, player_history_action, player_history_timestamp, player_name, player_history_reason, player_history_detail) VALUES (&PID, &Server, 6, UNIX_TIMESTAMP(NOW()), \'&Name\',  \'Kicked\', \'&Final\')',0,0),(5,1,'tempban','tempban &Player','tempbanclient &Player &Final','tell &Sender ^1pm: ^7Success|say ^1&Player ^7temp banned for ^1&Final',1,'INSERT INTO rcon_player_history (player_id, server_id, player_history_action, player_history_timestamp, player_name, player_history_reason, player_history_detail) VALUES (&PID, &Server, 5, UNIX_TIMESTAMP(NOW()), \'&Name\',  \'Temporary Ban\', \'&Final\')',0,0),(6,1,'ban','ban &Player','banclient &Player|clientkick &Player &Final','tell &Sender ^1pm: ^7Success|say ^1&Player ^7banned for ^1&Final',1,'INSERT INTO rcon_player_history (player_id, server_id, player_history_action, player_history_timestamp, player_name, player_history_reason, player_history_detail) VALUES (&PID, &Server, 5, UNIX_TIMESTAMP(NOW()), \'&Name\',  \'Permanent Ban\', \'&Final\')',0,0),(7,1,'rank','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(8,1,'globalrank','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(9,1,'globalstats','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(10,1,'topstats','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(11,1,'topkdr','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(12,1,'topdeaths','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(13,1,'topsuicides','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(14,1,'rules','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(15,1,'nextmap','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(16,1,'stuck','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(17,1,'topcaptures','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(18,1,'promote','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(19,1,'demote','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(20,1,'players','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(21,1,'warn','warn &Player','tell &Player ^1WARNING: ^7&Final','tell &Sender ^1pm: ^7Success',1,'INSERT INTO rcon_player_history (player_id, server_id, player_history_action, player_history_timestamp, player_name, player_history_reason, player_history_detail) VALUES (&PID, &Server, 7, UNIX_TIMESTAMP(NOW()), \'&Name\', \'Warning\', \'&Final\')',0,0),(22,1,'help','EXTERNAL','EXTERNAL','EXTERNAL',0,NULL,0,0),(23,1,'vip','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(24,1,'claimserver','EXTERNAL','EXTERNAL','EXTERNAL',0,NULL,0,0),(25,1,'me','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(26,1,'wallofshame','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(27,1,'unban','unban &Player','unbanuser &Name','tell &Sender ^1pm: ^7Success',0,NULL,0,0),(28,1,'globalme','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(29,1,'closekills','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(30,1,'timedban','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(31,1,'scrim','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(33,1,'restart','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(32,1,'group','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(34,1,'lock','lock &Value1','setadmindvar g_password &Value1',NULL,1,NULL,1,0),(35,1,'rotate','rotate','map_rotate',NULL,1,NULL,1,0),(36,1,'unlock','unlock','setadmindvar g_password \"\"',NULL,1,NULL,1,0),(37,1,'statreset','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(38,1,'shorthand','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(39,1,'guid','EXTERNAL','EXTERNAL','EXTERNAL',1,NULL,0,0),(51,1,'singleplayer','singleplayer &Game','playlist 0|playlist_enabled 0|g_gametype &Game','tell &Sender ^1pm: ^7Success|say Next single player game ^1&Game',1,NULL,1,0),(52,1,'fastrestart','fastrestart','fast_restart',NULL,1,NULL,1,0);
/*!40000 ALTER TABLE `rcon_commands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rcon_game`
--

DROP TABLE IF EXISTS `rcon_game`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_game` (
  `game_id` int(11) NOT NULL,
  `game_value` varchar(5) NOT NULL,
  `game_name` varchar(20) NOT NULL,
  `game_shorthand` varchar(6) NOT NULL,
  PRIMARY KEY (`game_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rcon_game`
--

LOCK TABLES `rcon_game` WRITE;
/*!40000 ALTER TABLE `rcon_game` DISABLE KEYS */;
INSERT INTO `rcon_game` VALUES (1,'gun','Gun Game','gun'),(2,'HLND','Sticks and Stones','sticks'),(3,'oic','One in the Chamber','oic'),(4,'shrp','Sharpshooter','sharp');
/*!40000 ALTER TABLE `rcon_game` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rcon_groups`
--

DROP TABLE IF EXISTS `rcon_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(30) NOT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rcon_groups`
--

LOCK TABLES `rcon_groups` WRITE;
/*!40000 ALTER TABLE `rcon_groups` DISABLE KEYS */;
INSERT INTO `rcon_groups` VALUES (1,'Owner'),(2,'Administrator'),(3,'Moderator'),(4,'Member'),(5,'Regular'),(6,'Player');
/*!40000 ALTER TABLE `rcon_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rcon_item_types`
--

DROP TABLE IF EXISTS `rcon_item_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_item_types` (
  `item_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_type_name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`item_type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rcon_item_types`
--

LOCK TABLES `rcon_item_types` WRITE;
/*!40000 ALTER TABLE `rcon_item_types` DISABLE KEYS */;
INSERT INTO `rcon_item_types` VALUES (1,'Sub-machine Guns'),(2,'Assault Rifles'),(3,'Shotguns'),(4,'Light Machine Guns'),(5,'Sniper Rifles'),(6,'Pistols'),(7,'Launchers'),(8,'Attachments'),(9,'Killstreaks'),(10,'Equipment');
/*!40000 ALTER TABLE `rcon_item_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rcon_items`
--

DROP TABLE IF EXISTS `rcon_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_type_id` int(11) NOT NULL,
  `server_type_id` int(11) NOT NULL,
  `item_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `item_code` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `item_damage` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=MyISAM AUTO_INCREMENT=89 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rcon_items`
--

LOCK TABLES `rcon_items` WRITE;
/*!40000 ALTER TABLE `rcon_items` DISABLE KEYS */;
INSERT INTO `rcon_items` VALUES (1,1,1,'AK74u','ak74u',''),(2,1,1,'MP5K','mp5k',''),(3,1,1,'Skorpion','skorpion',''),(4,1,1,'Spectre','spectre',''),(5,1,1,'MPL','mpl',''),(6,1,1,'Kiparis','kiparis',''),(7,1,1,'PM63','pm63',''),(8,1,1,'MAC11','mac11',''),(9,1,1,'Uzi','uzi',''),(10,2,1,'AK47','ak47',''),(11,2,1,'M16','m16',''),(12,2,1,'M14','m14',''),(13,2,1,'Famas','famas',''),(14,2,1,'FAL','fnfal',''),(15,2,1,'Galil','galil',''),(16,2,1,'Aug','aug',''),(17,2,1,'Commando','commando',''),(18,2,1,'Enfield','enfield',''),(19,2,1,'G11','g11',''),(20,3,1,'Olympia','rottweil72',''),(21,3,1,'Stakeout','ithaca',''),(22,3,1,'HS10','hs10',''),(23,3,1,'SPAS-12','spas',''),(24,4,1,'M60','m60',''),(25,4,1,'Stoner63','stoner63',''),(26,4,1,'RPK','rpk',''),(27,4,1,'HK21','hk21',''),(28,5,1,'Dragunov','dragunov',''),(29,5,1,'WA2000','wa2000',''),(30,5,1,'L96A1','l96a1',''),(31,5,1,'PSG1','psg1',''),(32,6,1,'ASP','asp',''),(33,6,1,'M1911','m1911',''),(34,6,1,'Makarov','makarov',''),(35,6,1,'Python','python',''),(36,6,1,'CZ75','cz75',''),(37,7,1,'M72 Law','m72_law',''),(38,7,1,'RPG','rpg',''),(39,7,1,'Strela-3','strela',''),(40,7,1,'China Lake','china_lake',''),(41,7,1,'Crossbow','crossbow,explosive_b',''),(42,7,1,'Ballistic Knife Melee','ballistic','MOD_MELEE'),(43,8,1,'Dual Wield','dw',''),(44,8,1,'Full Auto Pistol','_auto_',''),(45,8,1,'Snub Nose','snub',''),(46,8,1,'Grenade Launcher','gl','MOD_GRENADE_SPLASH,MOD_IMPACT'),(47,8,1,'Dual Clip','dualclip',''),(48,8,1,'Silencer','silencer',''),(49,8,1,'Extended Mag','extclip',''),(50,8,1,'Red Dot Scope','elbit',''),(51,8,1,'Reflex Scope','reflex',''),(52,8,1,'Master Key','mk','MOD_PISTOL_BULLET'),(53,8,1,'Flamethrower','ft','MOD_BURNED'),(54,8,1,'Speed Reloader','speed',''),(55,8,1,'Grip','grip',''),(56,8,1,'Variable Scope','vzoom',''),(57,8,1,'Rapid Fire','rf',''),(58,8,1,'ACOG Scope','acog',''),(59,8,1,'Low Power Scope','lps',''),(60,8,1,'Upg. Iron Sights','upgradesight',''),(61,8,1,'IR Scope','ir',''),(62,9,1,'RC-XD','rcbomb',''),(63,9,1,'Napalm Strike','napalm',''),(64,9,1,'Death Machine','minigun_mp',''),(65,9,1,'Chopper Gunner','huey',''),(66,9,1,'Mortar Team','mortar',''),(67,9,1,'Attack Chopper','cobra',''),(68,9,1,'Gunship','hind',''),(69,9,1,'Sentry Gun','turret',''),(70,9,1,'Valkyrie Rockets','m220',''),(71,9,1,'Rolling Thunder','airstrike',''),(72,9,1,'Attack Dogs','dog_bite',''),(73,9,1,'Grim Reaper','m202_flash',''),(74,10,1,'Second Chance','second_chance',''),(75,10,1,'Knife','knife_mp',''),(76,10,1,'Frag','frag',''),(77,10,1,'Semtex','sticky',''),(78,10,1,'Tomahawk','hatchet',''),(79,10,1,'Smoke','willy_pete',''),(80,10,1,'Flash','flash_grenade',''),(81,10,1,'Decoy','nightingale',''),(82,10,1,'Concussion','concussion',''),(83,10,1,'Nova Gas','tabun',''),(84,10,1,'Claymore','claymore',''),(85,10,1,'C4','satchel',''),(86,7,1,'Ballistic Knife Projectile','ballistic','MOD_PISTOL_BULLET,MOD_HEAD_SHOT');
/*!40000 ALTER TABLE `rcon_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rcon_mappack_maps`
--

DROP TABLE IF EXISTS `rcon_mappack_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_mappack_maps` (
  `map_id` int(11) NOT NULL,
  `mappack_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rcon_mappack_maps`
--

LOCK TABLES `rcon_mappack_maps` WRITE;
/*!40000 ALTER TABLE `rcon_mappack_maps` DISABLE KEYS */;
INSERT INTO `rcon_mappack_maps` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,2),(16,2),(17,2),(18,2),(19,3),(20,3),(21,3),(22,3),(24,4),(23,4),(25,4),(26,4);
/*!40000 ALTER TABLE `rcon_mappack_maps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rcon_mappacks`
--

DROP TABLE IF EXISTS `rcon_mappacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_mappacks` (
  `mappack_id` int(11) NOT NULL AUTO_INCREMENT,
  `mappack_name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`mappack_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rcon_mappacks`
--

LOCK TABLES `rcon_mappacks` WRITE;
/*!40000 ALTER TABLE `rcon_mappacks` DISABLE KEYS */;
INSERT INTO `rcon_mappacks` VALUES (1,'Black Ops'),(2,'First Strike'),(3,'Escalation'),(4,'Annihilation');
/*!40000 ALTER TABLE `rcon_mappacks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rcon_maps`
--

DROP TABLE IF EXISTS `rcon_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_maps` (
  `map_id` int(11) NOT NULL AUTO_INCREMENT,
  `server_type_id` int(11) NOT NULL,
  `map_file` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `map_name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`map_id`)
) ENGINE=MyISAM AUTO_INCREMENT=53 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rcon_maps`
--

LOCK TABLES `rcon_maps` WRITE;
/*!40000 ALTER TABLE `rcon_maps` DISABLE KEYS */;
INSERT INTO `rcon_maps` VALUES (14,1,'mp_russianbase','WMD'),(13,1,'mp_villa','Villa'),(12,1,'mp_mountain','Summit'),(18,1,'mp_stadium','Stadium'),(11,1,'mp_radiation','Radiation'),(10,1,'mp_nuked','Nuketown'),(22,1,'mp_zoo','Zoo'),(17,1,'mp_kowloon','Kowloon'),(8,1,'mp_havoc','Jungle'),(7,1,'mp_cairo','Havana'),(6,1,'mp_hanoi','Hanoi'),(5,1,'mp_duga','Grid'),(4,1,'mp_firingrange','Firing Range'),(16,1,'mp_discovery','Discovery'),(3,1,'mp_crisis','Crisis'),(2,1,'mp_cracked','Cracked'),(15,1,'mp_berlinwall2','Berlin Wall'),(1,1,'mp_array','Array'),(9,1,'mp_cosmodrome','Launch'),(19,1,'mp_gridlock','Convoy'),(21,1,'mp_outskirts','Stockpile'),(20,1,'mp_hotel','Hotel'),(24,1,'mp_area51','Hangar 18'),(23,1,'mp_drivein','Drive In'),(25,1,'mp_golfcourse','Hazard'),(26,1,'mp_silo','Silo');
/*!40000 ALTER TABLE `rcon_maps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rcon_mode_types`
--

DROP TABLE IF EXISTS `rcon_mode_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_mode_types` (
  `mode_type_id` int(11) NOT NULL,
  `mode_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `mode_type_players` int(11) NOT NULL,
  PRIMARY KEY (`mode_type_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rcon_mode_types`
--

LOCK TABLES `rcon_mode_types` WRITE;
/*!40000 ALTER TABLE `rcon_mode_types` DISABLE KEYS */;
INSERT INTO `rcon_mode_types` VALUES (1,1,1,18),(32,1,1,12),(2,1,2,18),(33,1,2,12),(3,1,3,18),(34,1,3,12),(4,1,4,18),(35,1,4,12),(5,1,5,18),(36,1,5,12),(6,1,6,18),(37,1,6,12),(7,1,7,18),(38,1,7,12),(8,1,8,18),(39,1,8,12),(25,1,9,18),(40,1,9,12),(9,2,1,18),(41,2,1,12),(10,2,2,18),(42,2,2,12),(11,2,3,18),(43,2,3,12),(12,2,4,18),(44,2,4,12),(13,2,5,18),(45,2,5,12),(14,2,6,18),(46,2,6,12),(15,2,7,18),(47,2,7,12),(16,2,8,18),(48,2,8,12),(30,2,9,18),(49,2,9,12),(17,3,1,18),(50,3,1,12),(18,3,2,18),(51,3,2,12),(19,3,3,18),(52,3,3,12),(20,3,4,18),(53,3,4,12),(21,3,5,18),(54,3,5,12),(22,3,6,18),(55,3,6,12),(23,3,7,18),(56,3,7,12),(24,3,8,18),(99,3,8,12),(31,3,9,18),(57,3,9,12);
/*!40000 ALTER TABLE `rcon_mode_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rcon_modes`
--

DROP TABLE IF EXISTS `rcon_modes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_modes` (
  `mode_id` int(11) NOT NULL AUTO_INCREMENT,
  `mode_shortcode` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `mode_longcode` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `mode_name` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`mode_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rcon_modes`
--

LOCK TABLES `rcon_modes` WRITE;
/*!40000 ALTER TABLE `rcon_modes` DISABLE KEYS */;
INSERT INTO `rcon_modes` VALUES (2,'hc','hard','Hardcore'),(3,'bb','bare','Barebones'),(1,'std','reg','Regular');
/*!40000 ALTER TABLE `rcon_modes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rcon_player_groups`
--

DROP TABLE IF EXISTS `rcon_player_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_player_groups` (
  `player_id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`player_id`,`server_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_player_history`
--

DROP TABLE IF EXISTS `rcon_player_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_player_history` (
  `player_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `player_history_action` int(11) NOT NULL,
  `player_history_timestamp` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `player_name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `player_history_reason` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `player_history_detail` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`player_history_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1531013 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_player_stats`
--

DROP TABLE IF EXISTS `rcon_player_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_player_stats` (
  `player_stats_id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `player_stats_kills` int(11) NOT NULL DEFAULT '0',
  `player_stats_deaths` int(11) NOT NULL DEFAULT '0',
  `player_stats_assists` int(11) NOT NULL DEFAULT '0',
  `player_stats_captures` int(11) NOT NULL DEFAULT '0',
  `player_stats_returns` int(11) NOT NULL DEFAULT '0',
  `player_stats_carrier_kills` int(11) NOT NULL DEFAULT '0',
  `player_stats_max_score` int(11) NOT NULL DEFAULT '0',
  `player_stats_suicides` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`player_stats_id`)
) ENGINE=MyISAM AUTO_INCREMENT=32805 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_players`
--

DROP TABLE IF EXISTS `rcon_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_players` (
  `player_id` int(11) NOT NULL AUTO_INCREMENT,
  `player_guid` int(20) NOT NULL,
  `player_name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `player_last_ip` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`player_id`),
  UNIQUE KEY `player_guid` (`player_guid`)
) ENGINE=MyISAM AUTO_INCREMENT=19699 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_rotation`
--

DROP TABLE IF EXISTS `rcon_rotation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_rotation` (
  `server_rotation_id` int(11) NOT NULL AUTO_INCREMENT,
  `rotation_group_id` int(11) NOT NULL,
  `rotation_sort` int(11) NOT NULL,
  `map_id` int(11) NOT NULL,
  `mode_type_id` int(11) NOT NULL,
  `game_id` int(11) DEFAULT '-1',
  PRIMARY KEY (`server_rotation_id`)
) ENGINE=MyISAM AUTO_INCREMENT=766 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_rotation_group`
--

DROP TABLE IF EXISTS `rcon_rotation_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_rotation_group` (
  `rotation_group_id` int(11) NOT NULL AUTO_INCREMENT,
  `rotation_group_name` varchar(100) NOT NULL,
  `server_id` int(11) NOT NULL,
  `rotation_group_active` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rotation_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_server_bans`
--

DROP TABLE IF EXISTS `rcon_server_bans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_server_bans` (
  `server_ban_id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `server_ban_when` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `server_ban_length` int(11) NOT NULL,
  `server_ban_reason` varchar(200) NOT NULL,
  PRIMARY KEY (`server_ban_id`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_server_commands`
--

DROP TABLE IF EXISTS `rcon_server_commands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_server_commands` (
  `server_command_id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `command_id` int(11) NOT NULL,
  `server_command_access` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`server_command_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2441 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_server_damage`
--

DROP TABLE IF EXISTS `rcon_server_damage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_server_damage` (
  `server_damage_id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `server_damage_total` int(11) NOT NULL,
  PRIMARY KEY (`server_damage_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25167 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_server_log`
--

DROP TABLE IF EXISTS `rcon_server_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_server_log` (
  `server_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `server_log_timestamp` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `server_log_command` char(1) COLLATE utf8_unicode_ci NOT NULL,
  `server_log_data` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`server_log_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9074788 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_server_messages`
--

DROP TABLE IF EXISTS `rcon_server_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_server_messages` (
  `server_message_id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `server_message_type` int(11) NOT NULL,
  `server_message_order` int(11) NOT NULL,
  `server_message_detail` varchar(100) NOT NULL,
  PRIMARY KEY (`server_message_id`)
) ENGINE=MyISAM AUTO_INCREMENT=340 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_server_players`
--

DROP TABLE IF EXISTS `rcon_server_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_server_players` (
  `server_id` int(11) NOT NULL,
  `server_slot` int(11) NOT NULL,
  `server_player_guid` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `server_player_name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `server_player_score` int(11) NOT NULL,
  `server_player_ip` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `server_player_ping` int(11) NOT NULL,
  PRIMARY KEY (`server_id`,`server_slot`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_server_restrictions`
--

DROP TABLE IF EXISTS `rcon_server_restrictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_server_restrictions` (
  `server_restriction_id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  PRIMARY KEY (`server_restriction_id`)
) ENGINE=MyISAM AUTO_INCREMENT=330 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_server_status`
--

DROP TABLE IF EXISTS `rcon_server_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_server_status` (
  `server_id` int(11) NOT NULL,
  `server_status_id` int(11) NOT NULL,
  `map_id` int(11) NOT NULL,
  `mode_type_id` int(11) NOT NULL,
  `rotation_sort` int(11) NOT NULL,
  `game_id` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`server_id`,`server_status_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_server_types`
--

DROP TABLE IF EXISTS `rcon_server_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_server_types` (
  `server_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `server_type_name` varchar(30) NOT NULL,
  `server_type_rcon_format` varchar(50) NOT NULL,
  PRIMARY KEY (`server_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rcon_server_types`
--

LOCK TABLES `rcon_server_types` WRITE;
/*!40000 ALTER TABLE `rcon_server_types` DISABLE KEYS */;
INSERT INTO `rcon_server_types` VALUES (1,'CoD - Black Ops','\\xff\\xff\\xff\\xff\\x00PWD\\x20CMD\\x00');
/*!40000 ALTER TABLE `rcon_server_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rcon_server_users`
--

DROP TABLE IF EXISTS `rcon_server_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_server_users` (
  `server_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `server_user_permissions` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`server_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_servers`
--

DROP TABLE IF EXISTS `rcon_servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_servers` (
  `server_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `server_name` varchar(45) NOT NULL,
  `server_ip` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `server_port` varchar(5) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `server_log_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `server_description` varchar(255) DEFAULT NULL,
  `server_rcon_password` varchar(12) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `server_ranked` tinyint(4) NOT NULL DEFAULT '1',
  `server_type_id` int(11) NOT NULL,
  `server_owner_id` int(11) NOT NULL DEFAULT '-1',
  `server_activation` varchar(13) NOT NULL,
  `server_monitor` int(11) NOT NULL DEFAULT '0',
  `server_warnings` int(11) DEFAULT '2',
  `server_show_restrictions` tinyint(4) DEFAULT '1',
  `server_last_run` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `server_pid` int(11) DEFAULT NULL,
  `server_max_ping` int(11) DEFAULT '0',
  PRIMARY KEY (`server_id`)
) ENGINE=MyISAM AUTO_INCREMENT=47 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_types`
--

DROP TABLE IF EXISTS `rcon_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_types` (
  `type_id` int(11) NOT NULL AUTO_INCREMENT,
  `type_shortcode` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `type_name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rcon_types`
--

LOCK TABLES `rcon_types` WRITE;
/*!40000 ALTER TABLE `rcon_types` DISABLE KEYS */;
INSERT INTO `rcon_types` VALUES (3,'ctf','Capture the Flag'),(8,'dem','Demolition'),(6,'dom','Domination'),(2,'ffa','Free for All'),(5,'hq','Headquarters'),(7,'sab','Sabotage'),(4,'snd','Search and Destroy'),(1,'tdm','Team Deathmatch'),(9,'tac','Team Tactical');
/*!40000 ALTER TABLE `rcon_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rcon_users`
--

DROP TABLE IF EXISTS `rcon_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `user_email` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
  `user_password` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `user_activation` varchar(13) COLLATE utf8_unicode_ci NOT NULL,
  `user_activated` int(11) NOT NULL,
  `user_logins` int(11) NOT NULL DEFAULT '0',
  `user_last_login` int(11) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_email` (`user_email`),
  UNIQUE KEY `user_name` (`user_name`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rcon_warnings`
--

DROP TABLE IF EXISTS `rcon_warnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rcon_warnings` (
  `warning_id` int(11) NOT NULL AUTO_INCREMENT,
  `server_restriction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `warning_count` int(11) NOT NULL,
  PRIMARY KEY (`warning_id`)
) ENGINE=InnoDB AUTO_INCREMENT=91592 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'rcon_deceptivestudios'
--
