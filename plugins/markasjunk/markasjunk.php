<?php

/**
 * Mark as Junk - SPFBL
 *
 * Plugin that adds a new button to the mailbox toolbar
 * to mark the selected messages as Junk and move them to the Junk folder
 * and send spam report to SPFBL server ( https://github.com/leonamp/SPFBL )
 *
 * Based on Mark as Junk plugin from Thomas Bruederli
 *
 * @version @package_version@
 * @license GNU GPLv3+
 * @author Ricardo Walter <ricardoa.walter@gmail.com>
 */
class markasjunk extends rcube_plugin
{
  public $task = 'mail';
  private $additional_headers = array('Received-SPFBL');
  private $spfbl_server = '54.94.137.168';
  private $spfbl_port = '9877';
  private $spfbl_timeout = 3;

  function init()
  {
    $rcmail = rcmail::get_instance();

    $this->register_action('plugin.markasjunk', array($this, 'request_action'));
    $this->add_hook('storage_init', array($this, 'storage_init'));

    if ($rcmail->action == '' || $rcmail->action == 'show') {
      $skin_path = $this->local_skin_path();
      $this->include_script('markasjunk.js');
      if (is_file($this->home . "/$skin_path/markasjunk.css"))
        $this->include_stylesheet("$skin_path/markasjunk.css");
      $this->add_texts('localization', true);

      $this->add_button(array(
        'type' => 'link',
        'label' => 'buttontext',
        'command' => 'plugin.markasjunk',
        'class' => 'button buttonPas junk disabled',
        'classact' => 'button junk',
        'title' => 'buttontitle',
        'domain' => 'markasjunk'), 'toolbar');
    }
  }

  function storage_init($args)
  {
    $flags = array(
      'JUNK'    => 'Junk',
      'NONJUNK' => 'NonJunk',
    );

    // register message flags
    $args['message_flags'] = array_merge((array)$args['message_flags'], $flags);
    $args['fetch_headers'] .= trim($args['fetch_headers'] . join(' ', $this->additional_headers));

    return $args;
  }

  function request_action()
  {
    $this->add_texts('localization');

    $rcmail  = rcmail::get_instance();
    $storage = $rcmail->get_storage();

    foreach (rcmail::get_uids() as $mbox => $uids) {
      $storage->unset_flag($uids, 'NONJUNK', $mbox);
      $storage->set_flag($uids, 'JUNK', $mbox);
      $message = new rcube_message($uids[0]);

      if (($junk_mbox = $rcmail->config->get('junk_mbox'))) {
        $rcmail->output->command('move_messages', $junk_mbox);
      }

      if(isset($message->headers->others['received-spfbl'])){
        $received_spfbl = $message->headers->others['received-spfbl'];
        $received_spfbl = explode(' ', $received_spfbl);
        $received_spfbl = $received_spfbl[1];

        $fp = fsockopen($this->spfbl_server, $this->spfbl_port, $errno, $errstr, $this->spfbl_timeout);
        if (!$fp) {
            echo "Erro: $errstr ($errno)<br />\n";
        } else {
            $out = "SPAM $received_spfbl\n";
            fwrite($fp, $out);
            $return = '';
            while (!feof($fp)) { $return .= fgets($fp, 128); }
            fclose($fp);
            $return = strtolower(trim($return));

            if(substr( $return, 0, 2 ) == 'ok'){
              $rcmail->output->command('display_message', $this->gettext('reportedasjunk'), 'confirmation');
            } else if($return == 'error: decryption'){
              $rcmail->output->command('display_message', "Ticket SPFBL inválido.", 'error');
            } else if($return == 'duplicate complain'){
              $rcmail->output->command('display_message', "Esta mensagem já foi reportada como spam", 'error');
            } else if($return == '') {
              $rcmail->output->command('display_message', "Timeout no envio do report", 'error');
            } else {
              $rcmail->output->command('display_message', "Erro: " . $return , 'error');
            }
        }
      }
    }
    $rcmail->output->send();
  }
}