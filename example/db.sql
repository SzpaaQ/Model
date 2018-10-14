-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Czas generowania: 14 Paź 2018, 19:04
-- Wersja serwera: 5.7.21-1ubuntu1
-- Wersja PHP: 7.1.22-1+ubuntu18.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Baza danych: `dev_bot`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `app_chat_users`
--

CREATE TABLE `app_chat_users` (
  `user_id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `token` varchar(110) DEFAULT NULL,
  `password` varchar(110) DEFAULT NULL,
  `username` varchar(15) DEFAULT NULL,
  `email` varchar(55) DEFAULT NULL,
  `avatar` int(55) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Zrzut danych tabeli `app_chat_users`
--

INSERT INTO `app_chat_users` (`user_id`, `application_id`, `token`, `password`, `username`, `email`, `avatar`) VALUES
(28, 47, '5ac2a30154dd3658ef5693def5746b02', 'password', 'azfasd', 'szpaq11@gmail.com', 165);



-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `app_contacts`
--

CREATE TABLE `app_contacts` (
  `contact_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Zrzut danych tabeli `app_contacts`
--

INSERT INTO `app_contacts` (`contact_id`, `user_id`, `member_id`) VALUES
(13, 27, 28),
(14, 27, 26);

--
-- Indeksy dla zrzutów tabel
--

--
-- Indexes for table `app_chat_users`
--
ALTER TABLE `app_chat_users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `app_contacts`
--
ALTER TABLE `app_contacts`
  ADD PRIMARY KEY (`contact_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT dla tabeli `app_chat_users`
--
ALTER TABLE `app_chat_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;
--
-- AUTO_INCREMENT dla tabeli `app_contacts`
--
ALTER TABLE `app_contacts`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
