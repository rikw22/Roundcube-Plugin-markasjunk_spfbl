# Roundcube "mark as junk" Plugin for SPFBL Blacklist
Este plugin adiciona o botão "Marcar como Spam" ao roundcube, ao clicar no botão ele envia o email para a lixeira e reporta a mensagem para o serviço SPFBL.

> ATENÇÃO: Este plugin está em fase beta e confita com o plugin original markasjunk,  até o momento os dois plugins não podem ser utilizados simultaneamente.

# Instruções de instalação
  - Copie a pasta 'plugins/markasjunk' para a pasta da sua instalação do Roundcube.
  - Abra o arquivo *config/config.inc.php*, localize a variável *$config['plugins']* a acrescente o valor *markasjunk* a variável. *(Ex: $config['plugins'] = array( "vcard_attachments", "markasjunk");*
  - Abra o arquivo *plugins/markasjunk/markasjunk.php*  e ajuste os parametros referentes ao servidor SPFBL  ($spfbl_server, $spfbl_port, $spfbl_timeout) conforme necessário.


# Créditos
Este plugin foi baseado no plugin "markasjunk" de Thomas Bruederli
