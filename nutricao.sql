
CREATE TABLE `adm` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `adm` (`id`, `email`, `senha`) VALUES
(1, 'amandamesquita@gmail.com', '$2y$10$EaBuhk0viR/nYlFu4EwH8e9eELN924OCznH5phnGMNg4L8/TNhRPG');

CREATE TABLE `agendamentos` (
  `id` int(11) NOT NULL,
  `profissional_id` int(11) DEFAULT NULL,
  `servico_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `data` date NOT NULL,
  `hora` time NOT NULL,
  `status` varchar(50) DEFAULT 'confirmado',
  `nome_cliente` varchar(100) DEFAULT NULL,
  `email_cliente` varchar(100) DEFAULT NULL,
  `telefone_cliente` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `agendamentos` (`id`, `profissional_id`, `servico_id`, `cliente_id`, `data`, `hora`, `status`, `nome_cliente`, `email_cliente`, `telefone_cliente`) VALUES
(7, 4, 1, 1, '2025-06-10', '14:30:00', 'confirmado', 'Amanda Mesquita De Farias', 'amandamesquita818@gmail.com', '(12) 97812-7295');

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `email`, `telefone`, `senha`, `google_id`) VALUES
(1, 'Amanda Mesquita De Farias', 'amandamesquita818@gmail.com', '(12) 97812-7295', '$2y$10$LnGKesEk10s/DW/Zaq2qN.xt2cDZ5MLMzFaTrkfrxZTi6kicHPcKK', NULL);

CREATE TABLE `profissionais` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `especialidade` varchar(100) DEFAULT NULL,
  `dias_disponiveis` varchar(100) DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fim` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `profissionais` (`id`, `nome`, `especialidade`, `dias_disponiveis`, `hora_inicio`, `hora_fim`) VALUES
(2, 'Dra. Helena Nogueira', 'Nutrição Clínica e Funcional', 'Segunda,Quarta,Sábado', '09:15:00', '16:00:00'),
(3, 'Dr. Rafael Lemos', 'Nutrição Clínica e Funcional', 'Segunda,Terça,Quinta,Sexta', '09:30:00', '18:00:00'),
(4, 'Dr. Lucas Viana', 'Nutrição Esportiva', 'Terça,Quarta,Quinta', '09:30:00', '18:40:00'),
(5, 'Dr. Caio Martins', 'Nutrição Esportiva', 'Segunda,Terça,Sábado', '09:50:00', '17:00:00'),
(6, 'Dra. Bianca Silveira', 'Nutrição Estética e Funcional', 'Segunda,Terça,Quarta,Quinta,Sexta', '10:30:00', '19:00:00'),
(7, 'Dr. Gustavo Rezende', 'Nutrição Materno-Infantil', 'Segunda,Terça,Quarta,Quinta,Sexta', '09:00:00', '17:30:00');

CREATE TABLE `servicos` (
  `id` int(11) NOT NULL,
  `servico` varchar(100) NOT NULL,
  `duracao` int(11) NOT NULL,
  `valor` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `servicos` (`id`, `servico`, `duracao`, `valor`) VALUES
(1, 'Avaliação Nutricional', 90, 300.00),
(2, 'Planejamento Alimentar Personalizado', 60, 250.00),
(3, 'Acompanhamento e Reavaliação', 60, 200.00),
(4, 'Consultoria para Empresas e Restaurantes', 120, 5000.00),
(5, 'Atendimento Online', 60, 190.00),
(6, 'Serviços Especiais', 90, 280.00),
(7, 'Consulta de Retorno', 60, 180.00),
(8, 'Consulta Inicial com Nutricionista', 70, 260.00);


ALTER TABLE `adm`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `agendamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profissional_id` (`profissional_id`),
  ADD KEY `servico_id` (`servico_id`),
  ADD KEY `cliente_id` (`cliente_id`);

ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `profissionais`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `adm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `agendamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `profissionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;


ALTER TABLE `servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;


ALTER TABLE `agendamentos`
  ADD CONSTRAINT `agendamentos_ibfk_1` FOREIGN KEY (`profissional_id`) REFERENCES `profissionais` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agendamentos_ibfk_2` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agendamentos_ibfk_3` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;


