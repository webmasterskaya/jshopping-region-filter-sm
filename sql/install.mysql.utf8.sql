create table if not exists `#__jshopping_shipping_method_price_states`
(
    `state_id`        INT(11) not null auto_increment,
    `country_id`      INT(11) not null,
    `sh_pr_method_id` INT(11) not null,
    index `state_id` (`state_id`),
    index `country_id` (`country_id`),
    index `sh_pr_method_id` (`sh_pr_method_id`)
) engine = InnoDB
  default charset = utf8mb4
  default collate = utf8mb4_unicode_ci;
