--
-- Database: `voting`
--

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mobile` varchar(12) NOT NULL,
  `aadhaar_no` varchar(12) NOT NULL,
  `voter_id` varchar(20) NOT NULL,
  `email` varchar(50) NOT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `role` int(1) NOT NULL,
  `status` int(1) NOT NULL,
  `votes` int(100) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `mobile`, `aadhaar_no`, `voter_id`, `email`, `otp`, `password`, `address`, `photo`, `role`, `status`, `votes`) VALUES
(1, 'Aarti', '9987749035', '123456789012', 'ABC1234567', 'aartiwaghmare23@gmail.com', NULL, '123', 'turbhe', 'balloon.jpeg', 1, 1, 0);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `mobile` (`mobile`),
  ADD UNIQUE KEY `aadhaar_no` (`aadhaar_no`),
  ADD UNIQUE KEY `voter_id` (`voter_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

