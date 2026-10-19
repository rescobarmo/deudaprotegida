ALTER TABLE `datos_deudaprotegida`
  ADD INDEX `idx_dp_fecha` (`fecha`),
  ADD INDEX `idx_dp_nombre` (`nombre`);
