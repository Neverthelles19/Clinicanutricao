-- Adicionar campo para controle de lembretes de 2 horas
ALTER TABLE agendamentos ADD COLUMN lembrete_2h_enviado TINYINT(1) NOT NULL DEFAULT 0;