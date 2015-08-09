SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `digimon`;
USE `digimon`;

-- Table structure for table `client`
DROP TABLE IF EXISTS `client`;
CREATE TABLE IF NOT EXISTS `client` (
`id` int(11) NOT NULL,
  `resourceId` int(11) NOT NULL,
  `userId` int(11) DEFAULT NULL,
  `token` int(11) NOT NULL,
  `active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- Table structure for table `user`
DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(40) NOT NULL,
  `fullname` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

INSERT INTO `user` (`id`, `username`, `password`, `fullname`, `email`, `active`) VALUES
(1, 'brad7928', 'b31f419ecb3b15706b6102681167472c0368f5b9', 'Bradly Sharpe', 'bradly@bradlysharpe.com.au', 1)

-- Indexes for table `client`
ALTER TABLE `client`
 ADD PRIMARY KEY (`id`), ADD KEY `userId` (`userId`);

-- Indexes for table `user`
ALTER TABLE `user`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `username` (`username`,`email`);

-- AUTO_INCREMENT for table `client`
ALTER TABLE `client`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

-- AUTO_INCREMENT for table `user`
ALTER TABLE `user`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

-- Constraints for table `client`
ALTER TABLE `client`
ADD CONSTRAINT `fk__client_id__user_id` FOREIGN KEY (`userId`) REFERENCES `user` (`id`);
