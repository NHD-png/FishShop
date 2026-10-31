-- MariaDB / XAMPP: chọn database website trước khi chạy.
-- Đổi tỷ lệ thành 10.000 VND = 1 EXP, giữ nguyên chi tiêu đã tích lũy.
ALTER TABLE customer_ranks
  DROP CONSTRAINT IF EXISTS chk_rank_exp,
  DROP CONSTRAINT IF EXISTS chk_rank_remainder;

UPDATE customer_ranks
SET total_exp = total_eligible_spend DIV 10000,
    spend_remainder = total_eligible_spend MOD 10000,
    current_rank = CASE
      WHEN total_eligible_spend DIV 10000 >= 100000 THEN 'thach_dau'
      WHEN total_eligible_spend DIV 10000 >= 50000 THEN 'chien_tuong'
      WHEN total_eligible_spend DIV 10000 >= 20000 THEN 'cao_thu'
      WHEN total_eligible_spend DIV 10000 >= 10000 THEN 'tinh_anh'
      WHEN total_eligible_spend DIV 10000 >= 3000 THEN 'kim_cuong'
      WHEN total_eligible_spend DIV 10000 >= 1000 THEN 'bach_kim'
      WHEN total_eligible_spend DIV 10000 >= 500 THEN 'vang'
      WHEN total_eligible_spend DIV 10000 >= 100 THEN 'bac'
      ELSE 'dong' END,
    last_rank_up_at = NULL;

ALTER TABLE customer_ranks
  ADD CONSTRAINT chk_rank_exp CHECK (total_exp = total_eligible_spend DIV 10000),
  ADD CONSTRAINT chk_rank_remainder CHECK (spend_remainder = total_eligible_spend MOD 10000);
