-- Adicionar campo para controle de lembretes de 2 horas
ALTER TABLE agendamentos ADD COLUMN lembrete_2h_enviado TINYINT(1) NOT NULL DEFAULT 0;

-- Adicionar campos para motivos de cancelamento e alteração
ALTER TABLE agendamentos ADD COLUMN motivo_cancelamento TEXT NULL;
ALTER TABLE agendamentos ADD COLUMN motivo_alteracao TEXT NULL;

-- Adicionar campo de status para os agendamentos
ALTER TABLE agendamentos ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'confirmado';

-- Criar índices para melhorar a performance das consultas
CREATE INDEX idx_agendamentos_cliente ON agendamentos(cliente_id);
CREATE INDEX idx_agendamentos_profissional ON agendamentos(profissional_id);
CREATE INDEX idx_agendamentos_data ON agendamentos(data);
CREATE INDEX idx_agendamentos_status ON agendamentos(status);