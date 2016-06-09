<?php
$GLOBALS['phpgw_info']['flags'] = array(
		'disable_Template_class' => True,
		'login'                  => True,
		'currentapp'             => 'login',
		'noheader'               => True
	);

if(!$_REQUEST['certificado'])
{
   echo '2'.chr(0x0D).chr(0x0A).'Certificado não foi apresentado.';
   exit();
}

if(file_exists('../header.inc.php'))
	{
		include('../header.inc.php');
	}
else
	{
	        echo '1'.chr(0x0D).chr(0x0A).'Arquivo header.inc.php n&atilde;o foi localizado.';
		exit();
	}

require_once('classes/CertificadoB.php');
require_once('classes/Verifica_Certificado.php');
include('classes/Verifica_Certificado_conf.php');

$cert =str_replace(chr(0x0A).chr(0x0A),chr(0x0A),$_REQUEST['certificado']);
$cert = troca_espaco_por_mais($cert);

$c = new certificadoB();
$c->certificado($cert);

if (!$c->apresentado)
{
   echo '2'.chr(0x0D).chr(0x0A).'Certificado não foi apresentado.';
   exit();
}

if (!$c->dados['CPF'])
{
   echo '2'.chr(0x0D).chr(0x0A).'Não foi possível obter o CPF do certificado apresentado.';
   exit();
}

$b = new Verifica_Certificado($c->dados,$cert);

// Testa se Certificado OK.
if(!$b->status)
{
   $msg = '3'.chr(0x0D).chr(0x0A).$b->msgerro;

   foreach($b->erros_ssl  as $linha)
   {
	$msg .= "\n" . $linha;
   }

   echo $msg;
   exit();
}

if ( (!empty($GLOBALS['phpgw_info']['server']['ldap_master_host'])) &&
	            (!empty($GLOBALS['phpgw_info']['server']['ldap_master_root_dn'])) &&
	            (!empty($GLOBALS['phpgw_info']['server']['ldap_master_root_pw'])) )
        {
                $ds = $GLOBALS['phpgw']->common->ldapConnect($GLOBALS['phpgw_info']['server']['ldap_master_host'],
			$GLOBALS['phpgw_info']['server']['ldap_master_root_dn'],
			$GLOBALS['phpgw_info']['server']['ldap_master_root_pw']);
        }
else
        {
                $ds = $GLOBALS['phpgw']->common->ldapConnect();
        }

if (!$ds)
     {
	echo '8'.chr(0x0D).chr(0x0A).'Não foi possível obter dados do usuario para login.';
	exit();
     }
     
    $filtro = 'uid='. $c->dados['2.16.76.1.3.1']['CPF'];
    $atributos = array();
    if(isset($GLOBALS['phpgw_info']['server']['atributoexpiracao']) && $GLOBALS['phpgw_info']['server']['atributoexpiracao'])
        {
            $atributos[] = $GLOBALS['phpgw_info']['server']['atributoexpiracao'];
        }
    else
        {
            $atributos[] = 'phpgwlastpasswdchange';
        }
     $atributos[] = "usercertificate";
     $atributos[] = "phpgwaccountstatus";
     $atributos[] = "cryptpassword";
     $atributos[] = "uid";

    $sr=ldap_search($ds, $GLOBALS['phpgw_info']['server']['ldap_context'],$filtro,$atributos);

    // Pega resultado ....
    $info = ldap_get_entries($ds, $sr);

    // Tem de achar só uma entrada.....ao menos uma....
    if($info["count"]!=1)
    {
	echo '4'.chr(0x0D).chr(0x0A).'Dados inválidos no diretório de usuários';
	ldap_close($ds);
	exit();
    }

// A conta expresso tem de estar ativa....
if($info[0]['phpgwaccountstatus'][0]!='A')
    {
	echo '5'.chr(0x0D).chr(0x0A).'Conta do usuario nao esta ativa no Expresso.';
	ldap_close($ds);
	exit();
    }

if($info[0]["cryptpassword"][0] && $info[0]["usercertificate"][0] && $cert == $info[0]["usercertificate"][0] )
    {
	echo '0'.chr(0x0D).chr(0x0A).$info[0]["uid"][0].chr(0x0D).chr(0x0A).$info[0]["cryptpassword"][0];
    }
else
    {
        if(!$info[0]["usercertificate"][0] || $cert != $info[0]["usercertificate"][0])
		{
			$user_info = array();
			$aux1 = $info[0]["dn"];
			$user_info['usercertificate'] = $cert;
			if(isset($GLOBALS['phpgw_info']['server']['atributoexpiracao']) && $GLOBALS['phpgw_info']['server']['atributoexpiracao'])
				{
					if(substr($info[0][$GLOBALS['phpgw_info']['server']['atributoexpiracao']][0],-1,1)=="Z")
						{
							$user_info[$GLOBALS['phpgw_info']['server']['atributoexpiracao']] = '19800101000000Z';
						}
					else
						{
							$user_info[$GLOBALS['phpgw_info']['server']['atributoexpiracao']] = '0';
						}
				}
			else
				{
                                        $user_info['phpgwlastpasswdchange'] = '0';
				}

			if(!ldap_modify($ds,$aux1,$user_info))
                            {
                                echo '7'.chr(0x0D).chr(0x0A).'Ocorreu um erro no acolhimento do certificado.',$aux1;
                            }
                        else
                            {
                                echo '6'.chr(0x0D).chr(0x0A).'Seu Certificado foi cadastrado. Sua senha foi expirada. Altere sua senha para concluir o processo.';
                            }
		}
	else
		{
			echo '6'.chr(0x0D).chr(0x0A).'Sua senha foi expirada. Altere sua senha para acessar o Expresso usando certificado digital.';
		}
    }
ldap_close($ds);
?>
