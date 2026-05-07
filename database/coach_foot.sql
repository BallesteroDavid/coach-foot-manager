-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : database
-- Généré le : jeu. 07 mai 2026 à 14:25
-- Version du serveur : 8.0.45
-- Version de PHP : 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `coach_foot`
--

-- --------------------------------------------------------

--
-- Structure de la table `app_user`
--

CREATE TABLE `app_user` (
  `id` int NOT NULL,
  `email` varchar(180) NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `club_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `app_user`
--

INSERT INTO `app_user` (`id`, `email`, `roles`, `password`, `firstname`, `lastname`, `club_id`) VALUES
(1, 'admin@coach-foot.test', '[\"ROLE_ADMIN_CLUB\"]', '$2y$13$WIc6FhMub8L83/5i9Lqv2OYIw8kejZWHulHGefN7l2Sjh55n8kPZy', 'Admin', 'Club', 1),
(2, 'julien.coach@test.fr', '[\"ROLE_COACH\"]', '$2y$13$MaoY.iLjMcqae9Lof3KN4OOWbDZCCT7tfde6xM0jMo3vjK5uz9KZO', 'Julien', 'Morel', 1),
(3, 'karim.coach@test.fr', '[\"ROLE_COACH\"]', '$2y$13$TGCeSmv/eXVau0Wh6HEr/eQkINGIiRwhY4DOIcGvP0MyPV1V9Tjpi', 'Karim', 'Benali', 1),
(4, 'matis.coach@test.fr', '[\"ROLE_COACH\"]', '$2y$13$xa68Hh5rCOLPnc5Iv8aT2eoZi.rYRmjlVFl4NZRXWEFtLPXX50gOK', 'Matis', 'Leroy', 1),
(5, 'laurent.coach@test.fr', '[\"ROLE_COACH\"]', '$2y$13$UhxPeFRsmciOmwbKJNL/kuacTyRpxq1I02Ncexjrpnwo0fcD6A0l2', 'Laurent', 'Rico', 1);

-- --------------------------------------------------------

--
-- Structure de la table `app_user_team`
--

CREATE TABLE `app_user_team` (
  `app_user_id` int NOT NULL,
  `team_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `app_user_team`
--

INSERT INTO `app_user_team` (`app_user_id`, `team_id`) VALUES
(2, 3),
(3, 5),
(3, 6),
(4, 2),
(5, 4);

-- --------------------------------------------------------

--
-- Structure de la table `category`
--

CREATE TABLE `category` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `age_min` int DEFAULT NULL,
  `age_max` int DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `club_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `category`
--

INSERT INTO `category` (`id`, `name`, `age_min`, `age_max`, `created_at`, `updated_at`, `club_id`) VALUES
(1, 'U15', 14, 15, '2026-05-05 14:42:20', NULL, 1),
(2, 'U17', 16, 17, '2026-05-05 14:43:15', NULL, 1),
(3, 'U9', 8, 9, '2026-05-06 09:00:54', NULL, 1),
(4, 'Seniors', 18, NULL, '2026-05-06 09:01:43', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `club`
--

CREATE TABLE `club` (
  `id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `club`
--

INSERT INTO `club` (`id`, `name`, `city`, `address`, `email`, `phone`) VALUES
(1, 'Coach Foot Club', 'Bordeaux', NULL, 'contact@coach-foot.test', '0600000000');

-- --------------------------------------------------------

--
-- Structure de la table `convocation`
--

CREATE TABLE `convocation` (
  `id` int NOT NULL,
  `status` varchar(30) NOT NULL,
  `comment` longtext,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `football_match_id` int NOT NULL,
  `player_id` int NOT NULL,
  `created_by_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20260430092444', '2026-04-30 09:25:22', 1162),
('DoctrineMigrations\\Version20260430125535', '2026-04-30 12:56:00', 762),
('DoctrineMigrations\\Version20260430143116', '2026-04-30 14:31:53', 890),
('DoctrineMigrations\\Version20260504105452', '2026-05-04 11:18:37', 674),
('DoctrineMigrations\\Version20260504130549', '2026-05-04 13:06:22', 849),
('DoctrineMigrations\\Version20260505074506', '2026-05-05 07:45:27', 792),
('DoctrineMigrations\\Version20260505121807', '2026-05-05 12:19:08', 1412),
('DoctrineMigrations\\Version20260505140158', '2026-05-05 14:02:24', 1455),
('DoctrineMigrations\\Version20260506093527', '2026-05-06 09:36:12', 1179),
('DoctrineMigrations\\Version20260506114709', '2026-05-06 11:47:40', 1886),
('DoctrineMigrations\\Version20260506134431', '2026-05-06 13:45:10', 1328),
('DoctrineMigrations\\Version20260506142709', '2026-05-06 14:27:37', 1609);

-- --------------------------------------------------------

--
-- Structure de la table `football_match`
--

CREATE TABLE `football_match` (
  `id` int NOT NULL,
  `match_date` date NOT NULL,
  `start_time` time NOT NULL,
  `location` varchar(255) NOT NULL,
  `location_type` varchar(30) NOT NULL,
  `opponent` varchar(255) NOT NULL,
  `competition` varchar(100) DEFAULT NULL,
  `home_score` int DEFAULT NULL,
  `away_score` int DEFAULT NULL,
  `status` varchar(30) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `team_id` int NOT NULL,
  `match_type` varchar(30) NOT NULL,
  `first_match_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `football_match`
--

INSERT INTO `football_match` (`id`, `match_date`, `start_time`, `location`, `location_type`, `opponent`, `competition`, `home_score`, `away_score`, `status`, `created_at`, `updated_at`, `team_id`, `match_type`, `first_match_id`) VALUES
(4, '2026-05-20', '15:15:00', 'Chevre', 'home', 'Pain au chocolat', 'regional', NULL, NULL, 'planned', '2026-05-05 09:14:17', NULL, 1, 'aller', NULL),
(6, '2026-05-19', '15:40:00', 'Aaaa', 'neutral', 'Pain au chocolat', NULL, NULL, NULL, 'planned', '2026-05-05 09:36:08', NULL, 1, 'simple', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `messenger_messages`
--

CREATE TABLE `messenger_messages` (
  `id` bigint NOT NULL,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `player`
--

CREATE TABLE `player` (
  `id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `jersey_number` int DEFAULT NULL,
  `status` varchar(30) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `team_id` int DEFAULT NULL,
  `guardian_email` varchar(180) DEFAULT NULL,
  `guardian_phone` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `player`
--

INSERT INTO `player` (`id`, `first_name`, `last_name`, `birth_date`, `email`, `phone`, `position`, `jersey_number`, `status`, `created_at`, `updated_at`, `team_id`, `guardian_email`, `guardian_phone`) VALUES
(2, 'Lucas', 'Martin', '2011-04-12', 'lucas.martin@test.fr', '0601010101', 'milieu', 8, 'active', '2026-05-06 07:23:43', NULL, 2, NULL, NULL),
(3, 'Enzo', 'Bernard', '2011-09-25', NULL, NULL, 'attaquant', 9, 'active', '2026-05-06 07:25:30', NULL, 2, 'parent.enzo@test.fr', '0602020202'),
(4, 'Mathis', 'Robert', '2012-02-18', 'mathis.robert@test.fr', NULL, 'defenseur', 4, 'injured', '2026-05-06 07:27:46', NULL, 2, 'parent.mathis@test.fr', '0603030303'),
(5, 'Nathan', 'Petit', '2009-06-03', 'nathan.petit@test.fr', '0604040404', 'gardien', 1, 'active', '2026-05-06 07:29:14', NULL, 4, NULL, NULL),
(6, 'Hugo', 'Moreau', '2009-11-14', NULL, NULL, 'milieu', 10, 'active', '2026-05-06 07:30:40', NULL, 4, 'parent.hugo@test.fr', '0605050505'),
(7, 'Adam', 'Leroy', '2010-01-30', 'adam.leroy@test.fr', '0606060606', 'defenseur', 5, 'suspended', '2026-05-06 07:32:35', NULL, 4, NULL, NULL),
(8, 'yanis', 'Garcia', '2009-08-21', 'yanis.garcia@test.fr', NULL, 'attaquant', 11, 'active', '2026-05-06 07:34:09', NULL, 3, 'parent.yanis@test.fr', '0607070707'),
(9, 'Léo', 'Fournier', '2010-03-09', 'leo.fournier@test.fr', '0608080808', 'milieu', 6, 'inactive', '2026-05-06 07:35:56', NULL, 3, NULL, NULL),
(10, 'Sami', 'Diallo', '2009-12-02', NULL, '0609090909', 'defenseur', 3, 'active', '2026-05-06 07:38:25', NULL, 3, 'parent.sami@test.fr', '0610101010'),
(11, 'Tom', 'Durand', '2017-04-12', NULL, NULL, 'attaquant', 7, 'active', '2026-05-06 09:08:12', NULL, 5, 'parent.tom@test.fr', '0611111111'),
(12, 'Noah', 'Lefevre', '2017-08-22', NULL, NULL, 'milieu', 10, 'active', '2026-05-06 09:09:40', NULL, 5, 'parent.noah@test.fr', '0622222222'),
(13, 'Ethan', 'Mercier', '2018-01-09', NULL, NULL, 'defenseur', 4, 'injured', '2026-05-06 09:12:02', NULL, 5, 'parent.ethan@test.fr', '0633333333'),
(14, 'Maxime', 'Laurent', '1998-06-18', 'maxime.laurent@test.fr', '0644444444', 'gardien', 1, 'active', '2026-05-06 09:13:30', NULL, 6, NULL, NULL),
(15, 'Thomas', 'Garnier', '1995-11-03', 'thomas.garnier@test.fr', '0655555555', 'milieu', 8, 'active', '2026-05-06 09:15:17', NULL, 6, NULL, NULL),
(16, 'Romain', 'Chevalier', '2027-02-27', 'romain.chevalier@test.fr', '0666666666', 'attaquant', 9, 'suspended', '2026-05-06 09:16:40', NULL, 6, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `season`
--

CREATE TABLE `season` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(30) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `club_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `season`
--

INSERT INTO `season` (`id`, `name`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`, `club_id`) VALUES
(1, '2025/2026', '2025-07-01', '2026-06-30', 'active', '2026-05-05 14:38:20', NULL, 1),
(2, '2026/2027', '2026-07-01', '2027-06-30', 'planned', '2026-05-05 14:40:35', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `team`
--

CREATE TABLE `team` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` longtext,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `club_id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `season_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `team`
--

INSERT INTO `team` (`id`, `name`, `description`, `created_at`, `updated_at`, `club_id`, `category_id`, `season_id`) VALUES
(1, 'Chocolatine', NULL, '2026-05-04 14:08:29', NULL, 1, NULL, NULL),
(2, 'U15 A', 'Équipe principale U15', '2026-05-05 14:47:54', NULL, 1, 1, 1),
(3, 'U17 B', 'Équipe secondaire U17', '2026-05-05 14:49:24', NULL, 1, 2, 1),
(4, 'U17 A', 'Équipe principale U17', '2026-05-05 14:50:39', NULL, 1, 2, 1),
(5, 'U9 A', 'Équipe enfant U9', '2026-05-06 09:04:35', NULL, 1, 3, 1),
(6, 'Seniors A', 'Équipe senior principale', '2026-05-06 09:05:48', NULL, 1, 4, 1);

-- --------------------------------------------------------

--
-- Structure de la table `training_attendance`
--

CREATE TABLE `training_attendance` (
  `id` int NOT NULL,
  `status` varchar(30) NOT NULL,
  `comment` longtext,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `training_session_id` int NOT NULL,
  `player_id` int NOT NULL,
  `updated_by_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `training_attendance`
--

INSERT INTO `training_attendance` (`id`, `status`, `comment`, `created_at`, `updated_at`, `training_session_id`, `player_id`, `updated_by_id`) VALUES
(4, 'present', NULL, '2026-05-07 08:00:09', NULL, 2, 14, 1),
(5, 'late', '15min', '2026-05-07 08:00:09', NULL, 2, 15, 1),
(6, 'present', NULL, '2026-05-07 08:00:09', NULL, 2, 16, 1);

-- --------------------------------------------------------

--
-- Structure de la table `training_session`
--

CREATE TABLE `training_session` (
  `id` int NOT NULL,
  `training_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location` varchar(255) NOT NULL,
  `theme` varchar(255) DEFAULT NULL,
  `comment` longtext,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `team_id` int NOT NULL,
  `created_by_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `training_session`
--

INSERT INTO `training_session` (`id`, `training_date`, `start_time`, `end_time`, `location`, `theme`, `comment`, `created_at`, `updated_at`, `team_id`, `created_by_id`) VALUES
(2, '2026-05-08', '12:00:00', '13:00:00', 'Darwin', 'Muscu', NULL, '2026-05-07 07:58:23', NULL, 6, 1);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `app_user`
--
ALTER TABLE `app_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`),
  ADD KEY `IDX_88BDF3E961190A32` (`club_id`);

--
-- Index pour la table `app_user_team`
--
ALTER TABLE `app_user_team`
  ADD PRIMARY KEY (`app_user_id`,`team_id`),
  ADD KEY `IDX_160EB5E34A3353D8` (`app_user_id`),
  ADD KEY `IDX_160EB5E3296CD8AE` (`team_id`);

--
-- Index pour la table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_64C19C161190A32` (`club_id`);

--
-- Index pour la table `club`
--
ALTER TABLE `club`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `convocation`
--
ALTER TABLE `convocation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_CONVOCATION_MATCH_PLAYER` (`football_match_id`,`player_id`),
  ADD KEY `IDX_C03B3F5FE1DA134D` (`football_match_id`),
  ADD KEY `IDX_C03B3F5F99E6F5DF` (`player_id`),
  ADD KEY `IDX_C03B3F5FB03A8386` (`created_by_id`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `football_match`
--
ALTER TABLE `football_match`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_8CE33ACE296CD8AE` (`team_id`),
  ADD KEY `IDX_8CE33ACE9EA69E8D` (`first_match_id`);

--
-- Index pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750` (`queue_name`,`available_at`,`delivered_at`,`id`);

--
-- Index pour la table `player`
--
ALTER TABLE `player`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_98197A65296CD8AE` (`team_id`);

--
-- Index pour la table `season`
--
ALTER TABLE `season`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_F0E45BA961190A32` (`club_id`);

--
-- Index pour la table `team`
--
ALTER TABLE `team`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_C4E0A61F61190A32` (`club_id`),
  ADD KEY `IDX_C4E0A61F12469DE2` (`category_id`),
  ADD KEY `IDX_C4E0A61F4EC001D1` (`season_id`);

--
-- Index pour la table `training_attendance`
--
ALTER TABLE `training_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_TRAINING_ATTENDANCE_SESSION_PLAYER` (`training_session_id`,`player_id`),
  ADD KEY `IDX_D75DB7F7DB8156B9` (`training_session_id`),
  ADD KEY `IDX_D75DB7F799E6F5DF` (`player_id`),
  ADD KEY `IDX_D75DB7F7896DBBDE` (`updated_by_id`);

--
-- Index pour la table `training_session`
--
ALTER TABLE `training_session`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_D7A45DA296CD8AE` (`team_id`),
  ADD KEY `IDX_D7A45DAB03A8386` (`created_by_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `app_user`
--
ALTER TABLE `app_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `category`
--
ALTER TABLE `category`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `club`
--
ALTER TABLE `club`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `convocation`
--
ALTER TABLE `convocation`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `football_match`
--
ALTER TABLE `football_match`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `player`
--
ALTER TABLE `player`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `season`
--
ALTER TABLE `season`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `team`
--
ALTER TABLE `team`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `training_attendance`
--
ALTER TABLE `training_attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `training_session`
--
ALTER TABLE `training_session`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `app_user`
--
ALTER TABLE `app_user`
  ADD CONSTRAINT `FK_88BDF3E961190A32` FOREIGN KEY (`club_id`) REFERENCES `club` (`id`);

--
-- Contraintes pour la table `app_user_team`
--
ALTER TABLE `app_user_team`
  ADD CONSTRAINT `FK_160EB5E3296CD8AE` FOREIGN KEY (`team_id`) REFERENCES `team` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_160EB5E34A3353D8` FOREIGN KEY (`app_user_id`) REFERENCES `app_user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `category`
--
ALTER TABLE `category`
  ADD CONSTRAINT `FK_64C19C161190A32` FOREIGN KEY (`club_id`) REFERENCES `club` (`id`);

--
-- Contraintes pour la table `convocation`
--
ALTER TABLE `convocation`
  ADD CONSTRAINT `FK_C03B3F5F99E6F5DF` FOREIGN KEY (`player_id`) REFERENCES `player` (`id`),
  ADD CONSTRAINT `FK_C03B3F5FB03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `app_user` (`id`),
  ADD CONSTRAINT `FK_C03B3F5FE1DA134D` FOREIGN KEY (`football_match_id`) REFERENCES `football_match` (`id`);

--
-- Contraintes pour la table `football_match`
--
ALTER TABLE `football_match`
  ADD CONSTRAINT `FK_8CE33ACE296CD8AE` FOREIGN KEY (`team_id`) REFERENCES `team` (`id`),
  ADD CONSTRAINT `FK_8CE33ACE9EA69E8D` FOREIGN KEY (`first_match_id`) REFERENCES `football_match` (`id`);

--
-- Contraintes pour la table `player`
--
ALTER TABLE `player`
  ADD CONSTRAINT `FK_98197A65296CD8AE` FOREIGN KEY (`team_id`) REFERENCES `team` (`id`);

--
-- Contraintes pour la table `season`
--
ALTER TABLE `season`
  ADD CONSTRAINT `FK_F0E45BA961190A32` FOREIGN KEY (`club_id`) REFERENCES `club` (`id`);

--
-- Contraintes pour la table `team`
--
ALTER TABLE `team`
  ADD CONSTRAINT `FK_C4E0A61F12469DE2` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  ADD CONSTRAINT `FK_C4E0A61F4EC001D1` FOREIGN KEY (`season_id`) REFERENCES `season` (`id`),
  ADD CONSTRAINT `FK_C4E0A61F61190A32` FOREIGN KEY (`club_id`) REFERENCES `club` (`id`);

--
-- Contraintes pour la table `training_attendance`
--
ALTER TABLE `training_attendance`
  ADD CONSTRAINT `FK_D75DB7F7896DBBDE` FOREIGN KEY (`updated_by_id`) REFERENCES `app_user` (`id`),
  ADD CONSTRAINT `FK_D75DB7F799E6F5DF` FOREIGN KEY (`player_id`) REFERENCES `player` (`id`),
  ADD CONSTRAINT `FK_D75DB7F7DB8156B9` FOREIGN KEY (`training_session_id`) REFERENCES `training_session` (`id`);

--
-- Contraintes pour la table `training_session`
--
ALTER TABLE `training_session`
  ADD CONSTRAINT `FK_D7A45DA296CD8AE` FOREIGN KEY (`team_id`) REFERENCES `team` (`id`),
  ADD CONSTRAINT `FK_D7A45DAB03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `app_user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
