<?php
	/**************************************************************************\
	* eGroupWare - Setup / Calendar                                            *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

    // Alterando dois campos da tabela phpgw_cal e phpgw_cal_holidays
	$oProc->query("ALTER TABLE phpgw_cal ALTER COLUMN cal_id set default nextval(('seq_phpgw_cal'::text)::regclass);");
	$oProc->query("ALTER TABLE phpgw_cal_holidays ALTER COLUMN hol_id set default nextval(('seq_phpgw_cal_holidays'::text)::regclass);");
	
	$oProc->query("SELECT pg_catalog.setval('seq_phpgw_cal', 1, false);");
	$oProc->query("SELECT pg_catalog.setval('seq_phpgw_cal_holidays', 1, false);");

	// Adicionando dois campos a tabela phpgw_cal
	$oProc->query("ALTER TABLE phpgw_cal ADD COLUMN last_status char(1) DEFAULT 'N'::bpchar;");
	$oProc->query("ALTER TABLE phpgw_cal ADD COLUMN last_update bigint DEFAULT (date_part('epoch'::text, ('now'::text)::timestamp(3) with time zone) * (1000)::double precision);");