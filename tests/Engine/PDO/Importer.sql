-- --------------------------------------------------------
-- Servidor:                     127.0.0.1
-- Versão do servidor:           10.4.13-MariaDB - mariadb.org binary distribution
-- OS do Servidor:               Win64
-- HeidiSQL Versão:              12.3.0.6589
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Copiando estrutura do banco de dados para php_test
DROP DATABASE IF EXISTS `php_test`;
CREATE DATABASE IF NOT EXISTS `php_test`; /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
USE `php_test`;

-- Copiando estrutura para função php_test.month_names_by_work_date
DROP FUNCTION IF EXISTS `month_names_by_work_date`;
DELIMITER //
CREATE FUNCTION `month_names_by_work_date`(`work_date` DATE,
	`acronym` TINYINT
) RETURNS text CHARSET utf8mb4
    DETERMINISTIC
BEGIN
	RETURN (
		CASE
			WHEN DATE_FORMAT(work_date, '%c') = 1 THEN IF(acronym = FALSE, 'Janeiro', 'Jan')
			WHEN DATE_FORMAT(work_date, '%c') = 2 THEN IF(acronym = FALSE, 'Fevereiro', 'Fev')
			WHEN DATE_FORMAT(work_date, '%c') = 3 THEN IF(acronym = FALSE, 'Março', 'Mar')
			WHEN DATE_FORMAT(work_date, '%c') = 4 THEN IF(acronym = FALSE, 'Abril', 'Abr')
			WHEN DATE_FORMAT(work_date, '%c') = 5 THEN IF(acronym = FALSE, 'Maio', 'Mai')
			WHEN DATE_FORMAT(work_date, '%c') = 6 THEN IF(acronym = FALSE, 'Junho', 'Jun')
			WHEN DATE_FORMAT(work_date, '%c') = 7 THEN IF(acronym = FALSE, 'Julho', 'Jul')
			WHEN DATE_FORMAT(work_date, '%c') = 8 THEN IF(acronym = FALSE, 'Agosto', 'Ago')
			WHEN DATE_FORMAT(work_date, '%c') = 9 THEN IF(acronym = FALSE, 'Setembro', 'Set')
			WHEN DATE_FORMAT(work_date, '%c') = 10 THEN IF(acronym = FALSE, 'Outubro', 'Out')
			WHEN DATE_FORMAT(work_date, '%c') = 11 THEN IF(acronym = FALSE, 'Novembro', 'Nov')
			WHEN DATE_FORMAT(work_date, '%c') = 12 THEN IF(acronym = FALSE, 'Dezembro', 'Dez')
		ELSE NULL END
	);
END//
DELIMITER ;

-- Copiando estrutura para função php_test.week_day_names_by_work_date
DROP FUNCTION IF EXISTS `week_day_names_by_work_date`;
DELIMITER //
CREATE FUNCTION `week_day_names_by_work_date`(`work_date` DATE,
	`acronym` TINYINT
) RETURNS text CHARSET utf8mb4
BEGIN
	RETURN (
		SELECT
		CASE 
			WHEN WEEKDAY(work_date) = 6 THEN IF(acronym = FALSE, 'Domingo', 'Dom')
			WHEN WEEKDAY(work_date) = 0 THEN IF(acronym = FALSE, 'Segunda-feira', 'Seg')
			WHEN WEEKDAY(work_date) = 1 THEN IF(acronym = FALSE, 'Terça-feira', 'Ter')
			WHEN WEEKDAY(work_date) = 2 THEN IF(acronym = FALSE, 'Quarta-feira', 'Qua')
			WHEN WEEKDAY(work_date) = 3 THEN IF(acronym = FALSE, 'Quinta-feira', 'Qui')
			WHEN WEEKDAY(work_date) = 4 THEN IF(acronym = FALSE, 'Sexta-feira', 'Sex')
			WHEN WEEKDAY(work_date) = 5 THEN IF(acronym = FALSE, 'Sábado', 'Sáb')
		ELSE NULL END AS `week_day`
	);
END//
DELIMITER ;

-- Copiando estrutura para tabela php_test.php_test
DROP TABLE IF EXISTS `php_test`;
CREATE TABLE IF NOT EXISTS `php_test` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `work_date` date NOT NULL,
  `start_time` time NOT NULL,
  `final_time` time NOT NULL,
  `elapsed_time` time NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- Copiando estrutura para view php_test.php_test_navigator_vw
DROP VIEW IF EXISTS `php_test_navigator_vw`;
-- Criando tabela temporária para evitar erros de dependência de VIEW
CREATE TABLE `php_test_navigator_vw` (
	`month` VARCHAR(2) NULL COLLATE 'utf8mb4_general_ci',
	`year` VARCHAR(4) NULL COLLATE 'utf8mb4_general_ci'
) ENGINE=MyISAM;

-- Copiando estrutura para procedure php_test.php_test_report
DROP PROCEDURE IF EXISTS `php_test_report`;
DELIMITER //
CREATE PROCEDURE `php_test_report`(
	IN `p_month` CHAR(2),
	IN `p_year` CHAR(4)
)
BEGIN
	SELECT
		(@row_number := @row_number + 1) AS `row_number`, 
		a.* 
	FROM (
	
		(
			SELECT 
				h.id, 
				week_day_names_by_work_date(h.work_date, TRUE) AS `week_day`,
				CONCAT(DATE_FORMAT(h.work_date, '%d'), '/', month_names_by_work_date(h.work_date, TRUE)) AS work_day,
				h.project,
				h.description,
				h.work_date,
				h.start_time,
				h.final_time,
				h.elapsed_time
			FROM php_test h
			WHERE YEAR(h.work_date) = p_year AND MONTH(h.work_date) = p_month
			ORDER BY h.work_date ASC 
		)
	
	UNION 
	
		(
			SELECT 
				NULL AS id, 
				NULL AS `week_day`,
				NULL AS work_day,
				NULL AS project,
				NULL AS description,
				LAST_DAY(CONCAT(p_year, '-', p_month, '-01')) AS work_date,
				NULL AS start_time,
				NULL AS final_time,
				SEC_TO_TIME(SUM(TIME_TO_SEC(b.elapsed_time))) AS elapsed_time
			FROM php_test b
			WHERE YEAR(b.work_date) = p_year AND MONTH(b.work_date) = p_month
			ORDER BY b.work_date DESC
		)
		
	) AS a,

	(SELECT @row_number := 0) r

	ORDER BY work_date ASC;

END//
DELIMITER ;

-- Copiando estrutura para view php_test.php_test_vw
DROP VIEW IF EXISTS `php_test_vw`;
-- Criando tabela temporária para evitar erros de dependência de VIEW
CREATE TABLE `php_test_vw` (
	`id` INT(10) UNSIGNED NOT NULL,
	`week_day` TEXT NULL COLLATE 'utf8mb4_general_ci',
	`work_day` MEDIUMTEXT NULL COLLATE 'utf8mb4_general_ci',
	`work_date` VARCHAR(10) NULL COLLATE 'utf8mb4_general_ci',
	`project` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_general_ci',
	`description` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_general_ci',
	`start_time` TIME NOT NULL,
	`final_time` TIME NOT NULL,
	`elapsed_time` TIME NOT NULL
) ENGINE=MyISAM;

-- Copiando estrutura para trigger php_test.php_test_before_insert
DROP TRIGGER IF EXISTS `php_test_before_insert`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `php_test_before_insert` BEFORE INSERT ON `php_test` FOR EACH ROW BEGIN
	SET NEW.elapsed_time = TIMEDIFF(NEW.final_time, NEW.start_time);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Copiando estrutura para trigger php_test.php_test_before_update
DROP TRIGGER IF EXISTS `php_test_before_update`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `php_test_before_update` BEFORE UPDATE ON `php_test` FOR EACH ROW BEGIN
	SET NEW.elapsed_time = TIMEDIFF(NEW.final_time, NEW.start_time);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Copiando estrutura para view php_test.php_test_navigator_vw
DROP VIEW IF EXISTS `php_test_navigator_vw`;
-- Removendo tabela temporária e criando a estrutura VIEW final
DROP TABLE IF EXISTS `php_test_navigator_vw`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `php_test_navigator_vw` AS SELECT 
	LPAD(MONTH(work_date), 2, '0') AS `month`,
	CAST(YEAR(work_date) AS CHAR(4)) AS `year`
FROM php_test
GROUP BY MONTH(work_date), YEAR(work_date) ;

-- Copiando estrutura para view php_test.php_test_vw
DROP VIEW IF EXISTS `php_test_vw`;
-- Removendo tabela temporária e criando a estrutura VIEW final
DROP TABLE IF EXISTS `php_test_vw`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `php_test_vw` AS SELECT 
	h.id, 
	week_day_names_by_work_date(h.work_date, FALSE) AS `week_day`,
	CONCAT(DATE_FORMAT(h.work_date, '%d'), ' de ', month_names_by_work_date(h.work_date, FALSE), ' de ', DATE_FORMAT(h.work_date, '%Y')) AS work_day,
	DATE_FORMAT(h.work_date, '%d/%m/%Y') AS work_date,
	h.project,
	h.description,
	h.start_time,
	h.final_time,
	h.elapsed_time
FROM php_test h ;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
