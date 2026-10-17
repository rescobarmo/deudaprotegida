ALTER TABLE `datos_deudaprotegina`
  ADD INDEX `idx_dp_fecha` (`fecha`),
  ADD INDEX `idx_dp_nombre` (`nombre`),
  ADD INDEX `idx_dp_otro` (`otro`);
