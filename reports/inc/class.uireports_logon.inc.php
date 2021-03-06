<?php
	/*************************************************************************************\
	* Expresso Relat�rio                										         *
	* by Elvio Rufino da Silva (elviosilva@yahoo.com.br, elviosilva@cepromat.mt.gov.br)  *
	* -----------------------------------------------------------------------------------*
	*  This program is free software; you can redistribute it and/or modify it			 *
	*  under the terms of the GNU General Public License as published by the			 *
	*  Free Software Foundation; either version 2 of the License, or (at your			 *
	*  option) any later version.														 *
	*************************************************************************************/

include_once(PHPGW_API_INC.'/class.aclmanagers.inc.php');

	class uireports_logon
	{
		var $public_functions = array
		(
			'report_logon_print_pdf'			=> True,
			'report_logon_group'				=> True,
			'report_logon_group_setor_print'	=> True,
			'show_access'						=> True,
			'get_user_info'						=> True,
			'css'								=> True
		);

		var $nextmatchs;
		var $user;
		var $functions;
		var $current_config;
		var $ldap_functions;
		var $db_functions;

		function uireports_logon()
		{
			$this->user			= CreateObject('reports.user');
			$this->nextmatchs	= CreateObject('phpgwapi.nextmatchs');
			$this->functions	= CreateObject('reports.functions');
			$this->ldap_functions = CreateObject('reports.ldap_functions');
			$this->db_functions = CreateObject('reports.db_functions');
			$this->fpdf = CreateObject('reports.uireports_fpdf'); // Class para PDF
									
			$c = CreateObject('phpgwapi.config','reports'); // cria o objeto relatorio no $c
			$c->read_repository(); // na classe config do phpgwapi le os dados da tabela phpgw_config where relatorio, como passagem acima
			$this->current_config = $c->config_data; // carrega os dados em do array no current_config

			if(!@is_object($GLOBALS['phpgw']->js))
			{
				$GLOBALS['phpgw']->js = CreateObject('phpgwapi.javascript');
			}
			$GLOBALS['phpgw']->js->validate_file('jscode','cc','reports');
		}
		
		function report_logon_print_pdf()
		{
			$account_lid = $GLOBALS['phpgw']->accounts->data['account_lid'];
			$acl = $this->functions->read_acl($account_lid);
			$raw_context = $acl['raw_context'];
			$contexts = $acl['contexts'];
			foreach ($acl['contexts_display'] as $index=>$tmp_context)
			{
				$context_display .= $tmp_context;
			}


			if (!$this->functions->check_acl( $account_lid, ACL_Managers::GRP_VIEW_USERS ))
			{
				$GLOBALS['phpgw']->redirect($GLOBALS['phpgw']->link('/reports/inc/access_denied.php'));
			}

			$grouplist = trim($_POST[setor]);
			$grouplist = trim(preg_replace("/-/","",$grouplist));

			$setordn = trim($_POST[setordn]);
			$subtitulo1 = trim($_POST[subtitulo]);
			
			$vnumacesso = trim($_POST[nacesso]);
			$numacesso = abs(round($vnumacesso));

			if ($vnumacesso==999){
				$numacesso = lang('Never login');
			}


			define('FPDF_FONTPATH','font/');
			$data_atual = date("d/m/Y"); 
			$titulo_system = $GLOBALS['phpgw_info']['apps']['reports']['title'];			

			$pdf=new uireports_fpdf("L");
			$pdf->Open();
			$pdf->AddPage();

			//Set font and colors
			$pdf->SetFont('Arial','B',14);
			$pdf->SetFillColor(0,0,0);
			$pdf->SetTextColor(0,0,200);
			$pdf->SetDrawColor(0,0,0);
			$pdf->SetLineWidth(.2);

			//Table header
			$SubTitulo = lang('reports title6');
			$SubTituloR = lang('Report Generated by Expresso Reports');
			$SubTitulo1 = $subtitulo1;
			$GLOBALS['phpgw_info']['apps']['reports']['subtitle'] = $SubTituloR;
			$pdf->Cell(0,8,$SubTitulo,0,1,'C',0);


			//Set font and colors
			$pdf->SetFont('Arial','B',8);
			$pdf->SetFillColor(0,0,0);
			$pdf->SetTextColor(0,0,200);
			$pdf->SetDrawColor(0,0,0);
			$pdf->SetLineWidth(.3);

//			$pdf->Cell(0,10,$SubTitulo1,0,1,'C',0);
			$pdf->MultiCell(0,3,$SubTitulo1,0,'C',0);
						
			$pdf->Cell(0,2,' ',0,1,'C',0);
			$pdf->Cell(0,5,'Data..: '.$data_atual,0,0,'L',0);
			$pdf->Cell(0,5,$titulo_system,0,1,'R',0);
												
			$account_info = $this->functions->get_list_user_sector_logon($setordn,$contexts,0,$numacesso);

			if (count($account_info))
			{ 
				//Restore font and colors
				$pdf->SetFont('Arial','',8);
				$pdf->SetFillColor(224,235,255);
				$pdf->SetTextColor(0);

				$pdf->Cell(60,5,lang('loginid'),1,0,'L',1);
				$pdf->Cell(60,5,lang('name'),1,0,'L',1);
				$pdf->Cell(70,5,lang('report email'),1,0,'L',1);
				$pdf->Cell(25,5,lang('Creation Date'),1,0,'C',1);
				$pdf->Cell(25,5,lang('Last access'),1,0,'C',1);
				$pdf->Cell(22,5,lang('Days without login'),1,0,'L',1);				
				$pdf->Cell(18,5,lang('status'),1,1,'L',1);

				
				while (list($null,$accountp) = each($account_info))
				{

					$access_log = $this->functions->show_access_log($accountp['account_id']);

					$access_log_array = explode("#",$access_log);
					
					$accountp['li_dias'] = $access_log_array[1];
					$accountp['li_date'] = $access_log_array[0];

					$tmpp[$contap]['account_id'] = $accountp['account_id']; 
					$tmpp[$contap]['account_lid'] = $accountp['account_lid'];
					$tmpp[$contap]['account_cn'] = $accountp['account_cn'];
					$tmpp[$contap]['account_status'] = $accountp['account_accountstatus'];
					$tmpp[$contap]['account_mail'] = $accountp['account_mail'];
					$tmpp[$contap]['createTimestamp'] = $accountp['createtimestamp'];
					$tmpp[$contap]['li_dias']= $accountp['li_dias'];
					$tmpp[$contap]['li_date']= $accountp['li_date'];

					$sortp[] = $accountp['li_dias'];

					if (count($sortp))
					{
						natcasesort($sortp);
					}

					$contap = $contap + 1;
				}

				while (list($key,$accountr1) = each($sortp))
				{
					if ($key == 0){
						$key = '';
						$returnp[] = $tmpp[$key];
					}else{
						$returnp[] = $tmpp[$key];
					}
				}

				$returnp = array_reverse($returnp); 

				while (list($null,$accountr) = each($returnp))
				{
						$row_cn = $accountr['account_cn'];
						$account_lid = $accountr['account_lid'];
						$row_mail = (!$accountr['account_mail'] ? lang('Without E-mail') : $accountr['account_mail']);
						$row_timestamp = substr($accountr['createTimestamp'], 6, 2)."/".substr($accountr['createTimestamp'], 4, 2)."/".substr($accountr['createTimestamp'], 0, 4);
						$row_li_date = $accountr['li_date'] == lang('Never login') ? lang('Never login') : $accountr['li_date'];
						$row_li_dias = $accountr['li_dias'];
						$row_status = $accountr['account_status'] == 'active' ? lang('Activated') : lang('Disabled');
						
						$pdf->Cell(60,5,$account_lid,0,0,'L',0);
						$pdf->Cell(60,5,$row_cn,0,0,'L',0);
						$pdf->Cell(70,5,$row_mail,0,0,'L',0);
						$pdf->Cell(25,5,$row_timestamp,0,0,'C',0);

						if ($row_li_date == lang('Never login'))
						{
							//Muda cor fonte
							$pdf->SetTextColor(256,0,0);
							$pdf->Cell(25,5,$row_li_date,0,0,'C',0);
							//Restaura cor fonte
							$pdf->SetTextColor(0);
						}
						else
						{
							//Restaura cor fonte
							$pdf->SetTextColor(0);
							$pdf->Cell(25,5,$row_li_date,0,0,'C',0);
						}

						$pdf->Cell(22,5,$row_li_dias,0,0,'C',0);																		

						if ($row_status == 'Ativado')
						{
							//Restaura cor fonte
							$pdf->SetTextColor(0);
							$pdf->Cell(18,5,$row_status,0,1,'L',0);
						}
						else
						{
							//Muda cor fonte
							$pdf->SetTextColor(256,0,0);
							$pdf->Cell(18,5,$row_status,0,1,'L',0);					
							//Restaura cor fonte
							$pdf->SetTextColor(0);
						}
				}
			}

			$pdf->Output();

			return; 
		}

		function report_logon_group()
		{
			$account_lid = $GLOBALS['phpgw']->accounts->data['account_lid'];
			$manager_acl = $this->functions->read_acl($account_lid);
			$raw_context = $acl['raw_context'];
			$contexts = $manager_acl['contexts'];
			$conta_context = count($manager_acl['contexts_display']);
			foreach ($manager_acl['contexts_display'] as $index=>$tmp_context)
			{
				$index = $index +1;

				if ($conta_context == $index)
				{
					$context_display .= $tmp_context;
				}
				else
				{
					$context_display .= $tmp_context.'&nbsp;|&nbsp;';
				}
			}
			
			// Verifica se tem acesso a este modulo
			if (!$this->functions->check_acl( $account_lid, ACL_Managers::GRP_VIEW_SECTORS ))
			{
				$GLOBALS['phpgw']->redirect($GLOBALS['phpgw']->link('/reports/inc/access_denied.php'));
			}

			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			
			$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['reports']['title'].' - '.lang('report of time without logging by Organization');
			$GLOBALS['phpgw']->common->phpgw_header();

			$p = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
			$p->set_file(array('groups'   => 'report_logon_group.tpl'));
			$p->set_block('groups','list','list');
			$p->set_block('groups','row','row');
			$p->set_block('groups','row_empty','row_empty');

			// Seta as variaveis padroes.
			$var = Array(
				'th_bg'					=> $GLOBALS['phpgw_info']['theme']['th_bg'],
				'back_url'				=> $GLOBALS['phpgw']->link('/reports/index.php'),
				'context_display'		=> $context_display
			);
			$p->set_var($var);
			$p->set_var($this->functions->make_dinamic_lang($p, 'list'));
			

			$GLOBALS['organizacaodn'] = $_POST['organizacaodn'];

			$contextsdn = $GLOBALS['organizacaodn'];

			// Save query
			$varorganizacao = explode(",",$contextsdn);
			$varorganizacao_nome = trim(strtoupper(preg_replace("/ou=/","",$varorganizacao[0])));
			$varorganizacao_nome = trim(strtoupper(preg_replace("/DC=/","",$varorganizacao_nome)));
			$user_logon = $GLOBALS['phpgw_info']['user'][account_lid];

			// carrega os grupos no listbox
			/************* DESCOMENTE ESTE PARTE DO CODIGO PARA LISTAR TODOS OS GRUPOS, SEM LISTAR POR USUSARIO LOGADO *********** 
			$sectors_info = $this->functions->get_groups_list($contexts,"*");
			$sectors_info_dn = $this->functions->get_groups_list_dn($contexts,"*");
			/* ************************************* FIM *********************************************************************** */

			$sectors_info = $this->functions->get_list_context_logon($user_logon,$contexts,0);
			$sectors_info_dn = $this->functions->get_list_groups_dn($user_logon,$contexts,0);

			if (!count($sectors_info))
			{
				$p->set_var('notselect',lang('No matches found'));
			}
			else
			{
				foreach($sectors_info as $context=>$sector)
				{
					
					$sectordn = $sectors_info_dn[$context];

					$tr_color = $this->nextmatchs->alternate_row_color($tr_color);
					
					if ($context == 0 && $contextsdn <> "")
					{
						if (trim(strtoupper($varorganizacao_nome)) ==  trim(strtoupper($sector)))
						{
							$sector_options .= "<option selected value='" .$contextsdn. "'>" .$varorganizacao_nome. "</option>";
						}
						else
						{
							$sector_options .= "<option selected value='" .$contextsdn. "'>" .$varorganizacao_nome. "</option>";
							$sector_options .= "<option value='" . $sectordn . "'>". $sector . "</option>";
						}

					}
					else
					{
						if ( trim(strtoupper($varorganizacao_nome)) !=  trim(strtoupper($sector)))
						{
							$sectorok = trim(strtoupper(preg_replace("/dc=/","",$sector)));
							$sectorok = trim(strtoupper(preg_replace("/dc=/","",$sectorok)));
							$sector_options .= "<option value='" . $sectordn . "'>". $sectorok . "</option>";
						}
					}

					$varselect = Array(
						'tr_color'    	=> $tr_color,
						'organizacaodn'	=> $contextsdn,
						'group_name'  	=> $sector_options
					);					
				}

				$p->set_var($varselect);
			}

			// ************** inicio carregar a sub-lista das organiza��es ****************
			//Admin make a search
			if ($GLOBALS['organizacaodn'] != '')
			{
				// Conta a quantidade de Usuario do grupo raiz
				$account_user = $this->functions->get_count_user_sector($contextsdn,$contexts,0);
				$totaluser = "(".$account_user.")";

				$p->set_var('organizacao', $varorganizacao_nome);
				$p->set_var('all_user', lang('all'));
				$p->set_var('total_user', $totaluser);

				$setorg = $contextsdn;

				$groups_info = $this->functions->get_sectors_list($contexts,$setorg);

				if (!count($groups_info))
				{
					$p->set_var('message',lang('No sector found'));
					$p->parse('rows','row_empty',True);				
				}
				else
				{
					$ii = 0;
					foreach($groups_info as $context=>$groups)
					{
						$explode_groups = explode("#",$groups);
						$ii = $ii + 1;
						$tr_color = $this->nextmatchs->alternate_row_color($tr_color);
						$varsuborg = Array(
							'tr_color'					=> $tr_color,
							'div_nacesso' 				=> "div_nacesso".$ii,
							'formname'					=> "form".$ii,
							'formsubmit'				=> "document.form".$ii.".submit()",
							'sector_name'				=> $explode_groups[0],
							'sector_namedn'				=> $explode_groups[1],
							'sector_namedn_completo'	=> $explode_groups[2],			
						);					

						$p->set_var($varsuborg);					
						$p->parse('rows','row',True);
					}
				}
			}

			$p->pfp('out','list');
		}

		function report_logon_group_setor_print()
		{
			$vnumacesso = trim($_POST[nacesso]);
			$numacesso = abs(round($vnumacesso));

			if ($vnumacesso==999){
				$numacesso = "Nunca logou";
			}

			$grouplist = trim($_POST[setor]);
			$grouplist = trim(preg_replace("/-/","",$grouplist));
			$organizacao = trim($_POST[organizacao]);
			$setordn = trim($_POST[setordn]);
			$organizacaodn = trim($_POST[organizacaodn]);
			$sectornamedncompleto = trim($_POST[sectornamedncompleto]);
			$Psectornamedncompleto = trim($_POST[Psectornamedncompleto]);

			if ($sectornamedncompleto=="" && $Psectornamedncompleto=="")
			{			
				$sectornamedncompleto = $organizacao;
			}
			else if ($sectornamedncompleto=="" && $Psectornamedncompleto <> "")
			{
				$sectornamedncompleto = $Psectornamedncompleto;
			}
			else
			{
				$sectornamedncompleto = $organizacao." | ".$sectornamedncompleto;
			}

			$data_atual = date("d/m/Y"); 
			$titulo_system = $GLOBALS['phpgw_info']['apps']['reports']['title'];
			
			$account_lid = $GLOBALS['phpgw']->accounts->data['account_lid'];
			$acl = $this->functions->read_acl($account_lid);
			$raw_context = $acl['raw_context'];
			$contexts = $acl['contexts'];
			foreach ($acl['contexts_display'] as $index=>$tmp_context)
			{
				$context_display .= $tmp_context;
			}
			// Verifica se o administrador tem acesso.
			if (!$this->functions->check_acl( $account_lid, ACL_Managers::GRP_VIEW_USERS ))
			{
				$GLOBALS['phpgw']->redirect($GLOBALS['phpgw']->link('/reports/inc/access_denied.php'));
			}

			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);
			$GLOBALS['phpgw_info']['flags']['app_header'] =  $GLOBALS['phpgw_info']['apps']['reports']['title'].' - '.lang('report user');
			$GLOBALS['phpgw']->common->phpgw_header();

			$p = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
			$p->set_file(Array('accounts' => 'report_logon_group_print.tpl'));
			$p->set_block('accounts','body');
			$p->set_block('accounts','rowpag');
			$p->set_block('accounts','row');
			$p->set_block('accounts','row_empty');
			
			$var = Array(
				'bg_color'					=> $GLOBALS['phpgw_info']['theme']['bg_color'],
				'th_bg'						=> $GLOBALS['phpgw_info']['theme']['th_bg'],
				'subtitulo'					=> lang('reports title6'),
				'subtitulo1'				=> $sectornamedncompleto,
				'context'					=> $raw_context,
				'titulo'					=> $titulo_system,
				'data_atual'				=> $data_atual,				
				'context_display'			=> $context_display,
				'organizacaodn'				=> $organizacaodn,
				'organizacao'				=> $organizacao,
				'sector_name'				=> $grouplist,
				'sector_namedn'				=> $setordn,
				'nacesso' 					=> $vnumacesso,
				'imapDelimiter'				=> $_SESSION['phpgw_info']['expresso']['email_server']['imapDelimiter']
			);

			$p->set_var($var);
			$p->set_var($this->functions->make_dinamic_lang($p, 'body'));

			// ************ PAGINA��O *******************************


			if ($vnumacesso==0){
				$numreg = $this->functions->get_num_users_sector($setordn,$contexts);
				if ($numreg==0){
					$p->set_var('message',lang('No user found'));
					$p->parse('rows','row_empty',True);
				}
				else {
					//url do paginador 
					$url = $GLOBALS['phpgw_info']['server']['webserver_url'].'/index.php?menuaction=reports.uireports_logon.report_logon_group_setor_print';
	
					// **** Grupo de paginas ****
					$gpag = $_POST[gpage];
	
					$grupopage = 20;
					
					if (!$gpag){
						$gpag  = 1;
					}
	
					// recebe o numero da pagina
					$npag = $_POST[page];
	
					// verifica se o get com o numero da pagina � nulo
					if (!$npag)
					{
						$npag = 1;
					}
	
					// conta total dos registros
					
					// numero de registro por pagina��o
					$numpage = 53;
					
					$tp = ceil($numreg/$numpage);
					$inicio = $page - 1;
					$inicio = $inicio * $numpage;
		
					// valor maximo de pagina��o
					$totalnpag =  (int)($tp/$grupopage);
					$restonpag = $tp % $grupopage;
	
					if ($restonpag > 0)
					{
						$maxtotalnpag = $totalnpag + 1;
					}
					else
					{
						$maxtotalnpag = $totalnpag;
					}
					// inicio fim para imprimir a pagina��o
					if( $tp > $grupopage)
					{
						// inicio do for da pagina��o
						if ($gpag <= ($totalnpag))
						{
							$fimgpg = $gpag * $grupopage;
							$iniciogpg = (($fimgpg - $grupopage)+1);
						}
						else
						{
							$iniciogpg = (($gpag - 1) * $grupopage);
							$fimgpg = $iniciogpg + $restonpag;
						}
					}
					else
					{
						// inicio do for da pagina��o
						$iniciogpg = 1;
						$fimgpg =  $tp;
					}
	

					// Imprime valores de contagen de registro e pagina
					$p->set_var('cont_user',$numreg);
					$p->set_var('cont_page',$tp);
					$p->set_var('page_now',$npag);
	
					// ********** busca no LDAP as informa��o paginada e imprime ****************
					$paginas = $this->functions->Paginate_user_logon('accounts',$setordn,$contexts,'cn','asc',$npag,$numpage,$numacesso);

					$tmpp = array();
					$contap = 0;
					while (list($null,$accountp) = each($paginas))
					{
						$access_log =  $this->functions->show_access_log($accountp['uidNumber'][0]);
	
						$access_log_array = explode("#",$access_log);
						
						$accountp['li_dias'][0] = $access_log_array[1];
						$accountp['li_date'][0] = $access_log_array[0];
	
						$tmpp[$contap]['account_id']	 = $accountp['uidNumber'][0]; 
						$tmpp[$contap]['account_lid'] = $accountp['uid'][0];
						$tmpp[$contap]['account_cn'] = $accountp['cn'][0];
						$tmpp[$contap]['account_status'] = $accountp['accountStatus'][0];
						$tmpp[$contap]['account_mail'] = $accountp['mail'][0];
						$tmpp[$contap]['createTimestamp'] = $accountp['createTimestamp'][0];
						$tmpp[$contap]['li_dias']= $accountp['li_dias'][0];
						$tmpp[$contap]['li_date']= $accountp['li_date'][0];
	
						$sortp[] = $accountp['li_dias'][0];
	
						if (count($sortp))
						{
							natcasesort($sortp);
						}
	
						$contap = $contap + 1;
					}
	
					while (list($key,$accountr1) = each($sortp))
					{
						$returnp[] = $tmpp[$key];
					}
	
					$returnp = array_reverse($returnp); 
					
					$ii = 0;
					
					while (list($null,$accountr) = each($returnp))
					{
						$this->nextmatchs->template_alternate_row_color($p);
						$ii = $ii + 1;
						
						$varr = array(
							'formname'		=> "formlog".$ii,
							'formsubmit'	=> "document.formlog".$ii.".submit()",
							'row_idnumber'	=> $accountr['account_id'],
							'row_loginid'	=> $accountr['account_lid'],
							'row_cn'		=> $accountr['account_cn'],
							'row_status'	=> $accountr['account_status'] == 'active' ? '<font color="#0033FF">Ativado</font> ' : '<font color="#FF0000">Desativado</font>',
							'row_mail'		=> (!$accountr['account_mail']?'<font color=red>Sem E-mail</font>':$accountr['account_mail']),
							'row_timestamp'		=> substr($accountr['createTimestamp'], 6, 2)."/".substr($accountr['createTimestamp'], 4, 2)."/".substr($accountr['createTimestamp'], 0, 4),
							'row_li_date'		=> $accountr['li_date'] ==lang('Never login') ? '<font color="#FF0000">'.$accountr['li_date'].'</font>' : $accountr['li_date'],
							'row_li_dias'		=> $accountr['li_dias']
						);
						
						$p->set_var($varr);
		
						$p->parse('rows','row',True);
					}
					// ********************** Fim ****************************
	
					// grupo de pagina anteriores
					if ($gpag > 1)
					{
						$gpaga = $gpag - 1;
						$varp = Array(
							'paginat'	=> 	"<form name='anterior' method='POST' action='$url'>
							<input type='hidden' name='setor' value='$grouplist'>
							<input type='hidden' name='organizacao' value='$organizacao'>
							<input type='hidden' name='setordn' value='$setordn'>
							<input type='hidden' name='organizacaodn' value='$organizacaodn'>
							<input type='hidden' name='page' value='$npag'>
							<input type='hidden' name='gpage' value='$gpaga'>
							<input type='hidden' name='Psectornamedncompleto' value='$sectornamedncompleto'>
							<div style='float:left;' onClick='document.anterior.submit()'><a href='#'>".lang('Previous Pages')."<<&nbsp;&nbsp;&nbsp;</a></div></form>"
						);
						$p->set_var($varp);						
		
							$p->parse('pages','rowpag',True);
					}
					// **** FIM *******
	
					// imprime a pagina��o
					if ($fimgpg > 1)
					{
						for($x = $iniciogpg; $x <= $fimgpg; $x++)
						{
							$varp = Array(
								'paginat'	=>  "<form name='form".$x."' method='POST' action='$url'>
								<input type='hidden' name='setor' value='$grouplist'>
								<input type='hidden' name='organizacao' value='$organizacao'>
								<input type='hidden' name='setordn' value='$setordn'>
								<input type='hidden' name='organizacaodn' value='$organizacaodn'>
								<input type='hidden' name='page' value='$x'>
								<input type='hidden' name='gpage' value='$gpag'>
								<input type='hidden' name='Psectornamedncompleto' value='$sectornamedncompleto'>
								<div style='float:left;' onClick='document.form".$x.".submit()'><a href='#'>$x&nbsp;</a></div></form>"
							);
	
							$p->set_var($varp);						
		
							$p->parse('pages','rowpag',True);
						}
					}
			
					// proximo grupo de pagina
					if ($gpag < $maxtotalnpag && $maxtotalnpag > 0) 
					{
						$gpagp = $gpag + 1;
						$varp = Array(
							'paginat'	=>  "<form name='proximo' method='POST' action='$url'>
							<input type='hidden' name='setor' value='$grouplist'>
							<input type='hidden' name='organizacao' value='$organizacao'>
							<input type='hidden' name='setordn' value='$setordn'>
							<input type='hidden' name='organizacaodn' value='$organizacaodn'>
							<input type='hidden' name='page' value='$npag'>
							<input type='hidden' name='gpage' value='$gpagp'>
							<input type='hidden' name='Psectornamedncompleto' value='$sectornamedncompleto'>
							<div style='float:left;' onClick='document.proximo.submit()'><a href='#'>&nbsp;&nbsp;&nbsp;>>".lang('Next Page')."</a></div></form>"
						);
						$p->set_var($varp);						
	
						$p->parse('pages','rowpag',True);
					}
	
					$vart = Array(
						'page'	=> $npag,
						'gpage'	=> $gpag
					);
		
					$p->set_var($vart);
					// ************************* FIM PAGINA��O ***********************							
				}
			}
			else{
				// ******** caso n�o for zero n�o vai paginar *****************
				//url do paginador 
				$url = $GLOBALS['phpgw_info']['server']['webserver_url'].'/index.php?menuaction=reports.uireports_logon.report_logon_group_setor_print';

				// conta total dos registros

				// ********** busca no LDAP as informa��o total e imprime ****************
				$paginas =$this->functions->get_list_user_sector_logon($setordn,$contexts,0,$numacesso);
				
				if(count($paginas)==0) {
					$p->set_var('message',lang('No user found'));
					$p->parse('rows','row_empty',True);
				}
				else {
					// Imprime valores de contagen de registro e pagina
					$p->set_var('cont_user',count($paginas));
					$p->set_var('cont_page',"1");
					$p->set_var('page_now',"1");
	
					$tmpp = array();
					$contap = 0;
					while (list($null,$accountp) = each($paginas))
					{
						$access_log =  $this->functions->show_access_log($accountp['account_id']);
	
						$access_log_array = explode("#",$access_log);
						
						if ($numacesso<>lang('Never login')){
							$accountp['li_dias'] = $access_log_array[1];
							$accountp['li_date'] = $access_log_array[0];
		
							$tmpp[$contap]['account_id']	 = $accountp['account_id']; 
							$tmpp[$contap]['account_lid'] = $accountp['account_lid'];
							$tmpp[$contap]['account_cn'] = $accountp['account_cn'];
							$tmpp[$contap]['account_status'] = $accountp['account_accountstatus'];
							$tmpp[$contap]['account_mail'] = $accountp['account_mail'];
							$tmpp[$contap]['createTimestamp'] = $accountp['createtimestamp'];
							$tmpp[$contap]['li_dias']= $accountp['li_dias'];
							$tmpp[$contap]['li_date']= $accountp['li_date'];
		
							$sortp[] = $accountp['li_dias'];
						}else{
							if ($numacesso == $access_log_array[0]) {
								$accountp['li_dias'] = $access_log_array[1];
								$accountp['li_date'] = $access_log_array[0];
			
								$tmpp[$contap]['account_id']	 = $accountp['account_id']; 
								$tmpp[$contap]['account_lid'] = $accountp['account_lid'];
								$tmpp[$contap]['account_cn'] = $accountp['account_cn'];
								$tmpp[$contap]['account_status'] = $accountp['account_accountstatus'];
								$tmpp[$contap]['account_mail'] = $accountp['account_mail'];
								$tmpp[$contap]['createTimestamp'] = $accountp['createtimestamp'];
								$tmpp[$contap]['li_dias']= $accountp['li_dias'];
								$tmpp[$contap]['li_date']= $accountp['li_date'];
			
								$sortp[] = $accountp['li_dias'];
							}
						}	
	
						if (count($sortp))
						{
							natcasesort($sortp);
						}
	
						$contap = $contap + 1;
					}
	
					while (list($key,$accountr1) = each($sortp))
					{
						$returnp[] = $tmpp[$key];
					}
	
					$returnp = array_reverse($returnp); 
					
					$ii = 0;
					
					while (list($null,$accountr) = each($returnp))
					{
						$this->nextmatchs->template_alternate_row_color($p);
						$ii = $ii + 1;
						
						$varr = array(
							'formname'		=> "formlog".$ii,
							'formsubmit'	=> "document.formlog".$ii.".submit()",
							'row_idnumber'	=> $accountr['account_id'],
							'row_loginid'	=> $accountr['account_lid'],
							'row_cn'		=> $accountr['account_cn'],
							'row_status'	=> $accountr['account_status'] == 'active' ? '<font color="#0033FF">'.lang('Activated').'</font> ' : '<font color="#FF0000">'.lang('Disabled').'</font>',
							'row_mail'		=> (!$accountr['account_mail']?'<font color=red>'.lang('Without E-mail').'</font>':$accountr['account_mail']),
							'row_timestamp'		=> substr($accountr['createTimestamp'], 6, 2)."/".substr($accountr['createTimestamp'], 4, 2)."/".substr($accountr['createTimestamp'], 0, 4),
							'row_li_date'		=> $accountr['li_date'] ==lang('Never login') ? '<font color="#FF0000">'.$accountr['li_date'].'</font>' : $accountr['li_date'],
							'row_li_dias'		=> $accountr['li_dias']
						);
						
						$p->set_var($varr);
		
						$p->parse('rows','row',True);
					}
				}
			// ********************** Fim ****************************
			}

			$p->pfp('out','body');
		}

		function get_user_info($userdn,$usercontexts,$usersizelimit)
		{
			$user_info = $this->functions->Paginate_user('accounts',$setordn,$contexts,'cn','asc',$npag,$numpage);

			return $user_info;
		}

		function css()
		{
			$appCSS = 
			'th.activetab
			{
				color:#000000;
				background-color:#D3DCE3;
				border-top-width : 1px;
				border-top-style : solid;
				border-top-color : Black;
				border-left-width : 1px;
				border-left-style : solid;
				border-left-color : Black;
				border-right-width : 1px;
				border-right-style : solid;
				border-right-color : Black;
				font-size: 12px;
				font-family: Tahoma, Arial, Helvetica, sans-serif;
			}
			
			th.inactivetab
			{
				color:#000000;
				background-color:#E8F0F0;
				border-bottom-width : 1px;
				border-bottom-style : solid;
				border-bottom-color : Black;
				font-size: 12px;
				font-family: Tahoma, Arial, Helvetica, sans-serif;				
			}
			
			.td_left {border-left:1px solid Gray; border-top:1px solid Gray; border-bottom:1px solid Gray;}
			.td_right {border-right:1px solid Gray; border-top:1px solid Gray; border-bottom:1px solid Gray;}
			
			div.activetab{ display:inline; }
			div.inactivetab{ display:none; }';
			
			return $appCSS;
		}

		function show_access()
		{	
			$vnumacesso = trim($_POST['nacesso']);
			$account_id = $_POST['account_id']; 
			$nome_usuario = $_POST['nome_usuario']; 
			$grouplist = $_POST['setor'];
			$organizacao = $_POST['organizacao'];
			$setordn = $_POST['setordn'];
			$organizacaodn = $_POST['organizacaodn'];
			$x = $_POST['page'];
			$gpag = $_POST['gpage'];
			$sectornamedncompleto = $_POST['Psectornamedncompleto'];

			$manager_account_lid = $GLOBALS['phpgw']->accounts->data['account_lid'];
			$tmp = $this->functions->read_acl($manager_account_lid);
			$manager_context = $tmp[0]['context'];
			
			// Verifica se tem acesso a este modulo
			if ((!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS )) && (!$this->functions->check_acl( $manager_account_lid, ACL_Managers::ACL_MOD_USERS_PASSWORD )))
			{
				$GLOBALS['phpgw']->redirect($GLOBALS['phpgw']->link('/reports/inc/access_denied.php'));
			}

			// Seta header.
			unset($GLOBALS['phpgw_info']['flags']['noheader']);
			unset($GLOBALS['phpgw_info']['flags']['nonavbar']);

			$GLOBALS['phpgw_info']['flags']['app_header'] = $GLOBALS['phpgw_info']['apps']['reports']['title'].' - '.lang('Access Log');
			$GLOBALS['phpgw']->common->phpgw_header();

			// Seta templates.
			$t = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);
			$t->set_file(array("body" => "accesslog.tpl"));
			$t->set_block('body','main');
			$t->set_block('body','row','row');

			// GET access log from the user.
			$GLOBALS['phpgw']->db->limit_query("select loginid,ip,li,lo,account_id,sessionid from phpgw_access_log WHERE account_id=".$account_id." order by li desc",$start,__LINE__,__FILE__);
			while ($GLOBALS['phpgw']->db->next_record())
			{
				$records[] = array(
					'loginid'    	=> $GLOBALS['phpgw']->db->f('loginid'),
					'ip'         	=> $GLOBALS['phpgw']->db->f('ip'),
					'li'         	=> $GLOBALS['phpgw']->db->f('li'),
					'lo'         	=> $GLOBALS['phpgw']->db->f('lo'),
					'account_id' 	=> $GLOBALS['phpgw']->db->f('account_id'),
					'sessionid'  	=> $GLOBALS['phpgw']->db->f('sessionid')
				);
			}

			// Seta as vcariaveis
			while (is_array($records) && list(,$record) = each($records))
			{
				$var = array(
					'row_loginid' => $record['loginid'],
					'row_ip'      => $record['ip'],
					'row_li'      => date("d/m/Y - H:i:s", $record['li']),
					'row_lo'      => $record['lo'] == 0 ? 0 : date("d/m/Y - H:i:s", $record['lo'])
				);
				$t->set_var($var);
				$t->fp('rows','row',True);
			}

			$var = Array(
				'th_bg'			=> $GLOBALS['phpgw_info']['theme']['th_bg'],
				'nome_usuario' 	=> $nome_usuario,				
				'back_url'		=>  "<form name='formlog' method='POST' action='./index.php?menuaction=reports.uireports_logon.report_logon_group_setor_print'>
				<input type='hidden' name='setor' value='$grouplist'>
				<input type='hidden' name='organizacao' value='$organizacao'>
				<input type='hidden' name='setordn' value='$setordn'>
				<input type='hidden' name='organizacaodn' value='$organizacaodn'>
				<input type='hidden' name='page' value='$x'>
				<input type='hidden' name='gpage' value='$gpag'>
				<input type='hidden' name='Psectornamedncompleto' value='$sectornamedncompleto'>
				<input type='hidden' name='nacesso' value='$vnumacesso'>
				<input name='button' type='submit' value='".lang('back')."'></div></form>"
			);

			$t->set_var($var);
			$t->set_var($this->functions->make_dinamic_lang($t, 'body'));
			$t->pfp('out','body');
		}
	}
?>
