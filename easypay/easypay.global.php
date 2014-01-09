<?php

/**
 * [BEGIN_COT_EXT]
 * Hooks=global
 * [END_COT_EXT]
 */
defined('COT_CODE') or die('Wrong URL.');

require_once cot_incfile('easypay', 'plug');
require_once cot_incfile('payments', 'module');

// Проверяем платежки на оплату услуги. Если есть то включаем услугу.
$easypay_cfg = cot_cfg_easypay();
foreach($easypay_cfg as $code => $opt)
{
	if ($easypays = cot_payments_getallpays('easypay.'.$code, 'paid'))
	{
		foreach ($easypays as $pay)
		{
			if (cot_payments_updatestatus($pay['pay_id'], 'done'))
			{
				if($pay['pay_userid'] > 0)
				{
					$customer = $db->query("SELECT * FROM $db_users WHERE user_id=".$pay['pay_userid'])->fetch();
				}
				else
				{
					$customer['user_name'] = $pay['pay_email'];
					$customer['user_email'] = $pay['pay_email'];
				}
				
				// отправляем админу детали операции на email
				$subject = $L['easypay_email_paid_admin_subject'];
				$body = sprintf($L['easypay_email_paid_admin_body'], $customer['user_name'], $pay['pay_desc'], $pay['pay_summ'].' '.$cfg['payments']['valuta'], $pay['pay_id'], cot_date('d.m.Y в H:i', $pay['pay_pdate']));
				cot_mail($cfg['adminemail'], $subject, $body);
				
				// отправляем пользователю детали операции на email
				$subject = $L['easypay_email_paid_customer_subject'];
				$body = sprintf($L['easypay_email_paid_customer_body'], $customer['user_name'], $pay['pay_desc'], $pay['pay_summ'].' '.$cfg['payments']['valuta'], $pay['pay_id'], cot_date('d.m.Y в H:i', $pay['pay_pdate']));
				cot_mail($customer['user_email'], $subject, $body);
				
				/* === Hook === */
				foreach (cot_getextplugins('easypay.done') as $pl)
				{
					include $pl;
				}
				/* ===== */

				/* === Hook === */
				foreach (cot_getextplugins('easypay.'.$code.'.done') as $pl)
				{
					include $pl;
				}
				/* ===== */
			}
		}
	}
}

?>