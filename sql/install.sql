create table `#__jshopping_shipping_method_price_states`
(
    `state_id`        INT(11) not null auto_increment,
    `country_id`      INT(11) not null,
    `sh_pr_method_id` INT(11) not null,
    index `state_id` (`state_id`),
    index `country_id` (`country_id`),
    index `sh_pr_method_id` (`sh_pr_method_id`)
) collate = 'utf8_general_ci'
  engine = InnoDB;
