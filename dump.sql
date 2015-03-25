-- MySQL dump 10.13  Distrib 5.5.41, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: test
-- ------------------------------------------------------
-- Server version	5.5.41-0ubuntu0.14.10.1

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
-- Table structure for table `one`
--

DROP TABLE IF EXISTS `one`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `one` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `a` int(11) DEFAULT NULL,
  `b` varchar(100) DEFAULT NULL,
  `c` datetime DEFAULT NULL,
  `d` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `a` (`a`,`d`),
  UNIQUE KEY `d` (`d`)
) ENGINE=InnoDB AUTO_INCREMENT=3001 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `one`
--

LOCK TABLES `one` WRITE;
/*!40000 ALTER TABLE `one` DISABLE KEYS */;
/*!40000 ALTER TABLE `one` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `three`
--

DROP TABLE IF EXISTS `three`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `three` (
  `a` int(11) DEFAULT NULL,
  `d` int(11) DEFAULT NULL,
  `f` char(10) DEFAULT NULL,
  UNIQUE KEY `d` (`d`,`f`),
  UNIQUE KEY `a_2` (`a`),
  KEY `a` (`a`,`d`),
  CONSTRAINT `three_ibfk_1` FOREIGN KEY (`a`, `d`) REFERENCES `two` (`a`, `d`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `three`
--

LOCK TABLES `three` WRITE;
/*!40000 ALTER TABLE `three` DISABLE KEYS */;
/*!40000 ALTER TABLE `three` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `two`
--

DROP TABLE IF EXISTS `two`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `two` (
  `a` int(11) DEFAULT NULL,
  `d` int(11) DEFAULT NULL,
  `e` blob,
  `one_id` int(10) unsigned NOT NULL,
  KEY `a` (`a`,`d`),
  KEY `one_id` (`one_id`),
  CONSTRAINT `two_ibfk_1` FOREIGN KEY (`one_id`) REFERENCES `one` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `two`
--

LOCK TABLES `two` WRITE;
/*!40000 ALTER TABLE `two` DISABLE KEYS */;
/*!40000 ALTER TABLE `two` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-03-25 11:00:00
