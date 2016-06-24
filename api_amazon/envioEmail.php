<?php

class Email
{
	/**
	 *Função para enviar email
	 * @param $destinatario, $assunto,$mensagem
	 */
	function enviaEmail($destinatario, $assunto,$mensagem)
	{
		$to = '';
		foreach ($destinatario as $key => $value) {
			$to = $value;

		// subject
		$subject = $assunto;

		// message
		$message = $mensagem;

		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

		// Additional headers


		$headers .= 'From: I.T <webmaster@polyhousestore.com>' . "\r\n";
		$headers .= 'Reply-To: I.T <webmaster@polyhousestore.com>' . "\r\n".
		"X-Mailer: PHP/" . phpversion();

		// Mail it
		mail($to, $subject, $message, $headers);
		}
		return 'Email Enviado!';
	}

}

/*
$enviaEmail = new Email();
$desti = array('Higor' => 'higor_rafael.sp@hotmail.com','Webmaster'=>'webmaster@polyhousestore.com.br');
$enviaEmail->enviaEmail($desti, 'Teste', 'Teste de envio de email');
*/