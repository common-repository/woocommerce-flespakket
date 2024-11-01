<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?php
		require_once(ABSPATH . 'wp-admin/admin.php');
		wp_register_style( 'wcflespakket-admin-styles', dirname(plugin_dir_url(__FILE__)) .  '/css/wcflespakket-admin-styles.css', array(), '', 'all' );
		wp_enqueue_style( 'wcflespakket-admin-styles' );		
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'media' );
		wp_enqueue_script( 'jquery' );
		do_action('admin_print_styles');
		do_action('admin_print_scripts');
	?>
</head>
<body>
<form  method="post" class="page-form">						
	<table class="widefat">
	<thead>
		<tr>
			<th>Export opties</td>
		</tr>
	</thead>
	<tbody>
		<?php $c = true; foreach ($data as $row) : ?>
		<tr <?php echo (($c = !$c)?' class="alternate"':'');?>>
			<td>
				<table>
					<tr>
						<td colspan="2"><strong>Bestelling <?php echo $row['ordernr']; ?></strong></td>
					</tr>
					<tr>
						<td class="ordercell">
							<table class="widefat">
								<thead>
									<tr>
										<th>#</th>
										<th>Productnaam</th>
										<th align="right">Gewicht (kg)</th>
									</tr>
								</thead>
								<tbody>
								<?php
								// $verpakkingsgewicht = (isset($this->settings['verpakkingsgewicht'])) ? preg_replace("/\D/","",$this->settings['verpakkingsgewicht'])/1000 : 0;
								$total_weight = 0; //$verpakkingsgewicht;
								foreach ($row['bestelling'] as $product) { 
									$total_weight += $product['total_weight'];?>
									<tr>
										<td><?php echo $product['quantity'].'x'; ?></td>
										<td><?php echo $product['name'].$product['variation']; ?></td>
										<td align="right"><?php echo number_format($product['total_weight'], 3, ',', ' '); ?></td>
									</tr>
								<?php } ?>
								</tbody>
								<tfoot>
									<tr>
										<td></td>
										<td>Totaal:</td>
										<td align="right"><?php echo number_format($total_weight, 3, ',', ' ');?></td>
									</tr>
								</tfoot>
							</table>
						</td>
						<td><p><?php
							if ( $row['landcode'] == 'NL' && ( empty($row['straat']) || empty($row['huisnummer']) ) ) { ?>
							<span style="color:red">Deze order bevat geen geldige straatnaam- en huisnummergegevens, en kan daarom niet worden ge-exporteerd! Waarschijnlijk is deze order geplaatst voordat de Flespakket plugin werd geactiveerd. De gegevens kunnen wel handmatig worden ingevoerd in het order scherm.</span>
							</p>
						</td>
					</tr>
							<?php } else {
							echo $row['formatted_address'].'<br/>'
							.$row['telefoon'].'<br/>'
							.$row['email']; ?></p>
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][naam]" value="<?php echo $row['naam'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][bedrijfsnaam]" value="<?php echo $row['bedrijfsnaam'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][straat]" value="<?php echo $row['straat'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][huisnummer]" value="<?php echo $row['huisnummer'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][huisnummertoevoeging]" value="<?php echo $row['huisnummertoevoeging'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][adres1]" value="<?php echo $row['adres1'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][adres2]" value="<?php echo $row['adres2'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][postcode]" value="<?php echo $row['postcode'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][woonplaats]" value="<?php echo $row['woonplaats'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][landcode]" value="<?php echo $row['landcode'] ?>">
							<input type="hidden" name="data[<?php echo $row['orderid']; ?>][gewicht]" value="<?php echo number_format($total_weight, 2, '.', ''); ?>">
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<table class="wcflespakket_settings_table">
								<?php if ( $row['landcode'] == 'NL' ) { ?>
								<tr>
									<td>Aantal colli</td>
									<td>
										<select name="data[<?php echo $row['orderid']; ?>][colli_amount]" class="colli-amount" data-orderid="<?php echo $row['orderid']; ?>">
											<option value="1" selected>1</option>
											<option value="2">2</option>
											<option value="3">3</option>
											<option value="4">4</option>
											<option value="5">5</option>
											<option value="6">6</option>
											<option value="7">7</option>
											<option value="8">8</option>
											<option value="9">9</option>
											<option value="10">10</option>
										</select>
									</td>
								</tr>
								<?php } // endif?>
								<tr>
									<td class="collo_1_label">Soort zending</td>
									<td>
										<?php if (!isset($this->settings['package'])) $this->settings['package'] = 'bottle_1'; ?>
										<select name="data[<?php echo $row['orderid']; ?>][package]">
											<option value="bottle_1" <?php selected("bottle_1", $this->settings['package'])?>>1 fles</option>
											<option value="bottle_2" <?php selected("bottle_2", $this->settings['package'])?>>2 flessen</option>
											<option value="bottle_3" <?php selected("bottle_3", $this->settings['package'])?>>3 flessen</option>
											<option value="bottle_6" <?php selected("bottle_6", $this->settings['package'])?>>6 flessen</option>
											<option value="bottle_12" <?php selected("bottle_12", $this->settings['package'])?>>12 flessen</option>
											<option value="bottle_18" <?php selected("bottle_18", $this->settings['package'])?>>18 flessen</option>
											<option value="other" <?php selected("other", $this->settings['package'])?>>anders</option>
										</select>
									</td>
								</tr>
								<?php
								if ( $row['landcode'] == 'NL' ) {
									for ($x=2; $x<=10; $x++) {
								?>
								<tr style="display:none">
									<td>Soort zending <?php echo $x;?></td>
									<td>
										<?php if (!isset($this->settings['package'])) $this->settings['package'] = 'bottle_1'; ?>
										<select name="data[<?php echo $row['orderid']; ?>][package_<?php echo $x;?>]" id="<?php echo $row['orderid'].'_package_'.$x;?>">
											<option value="bottle_1" <?php selected("bottle_1", $this->settings['package'])?>>1 fles</option>
											<option value="bottle_2" <?php selected("bottle_2", $this->settings['package'])?>>2 flessen</option>
											<option value="bottle_3" <?php selected("bottle_3", $this->settings['package'])?>>3 flessen</option>
											<option value="bottle_6" <?php selected("bottle_6", $this->settings['package'])?>>6 flessen</option>
											<option value="bottle_12" <?php selected("bottle_12", $this->settings['package'])?>>12 flessen</option>
											<option value="bottle_18" <?php selected("bottle_18", $this->settings['package'])?>>18 flessen</option>
											<option value="other" <?php selected("other", $this->settings['package'])?>>anders</option>
										</select>
									</td>
								</tr>
								<?php
									} //endfor
								} // endif
								?>
								<tr>
									<?php if (!isset($this->settings['email'])) $this->settings['email'] = ''; ?>
									<td>Email adres koppelen</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][email]" value="<?php echo $row['email']; ?>" <?php checked("1", $this->settings['email'])?>></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['telefoon'])) $this->settings['telefoon'] = ''; ?>
									<td>Telefoonnummer koppelen</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][telefoon]" value="<?php echo $row['telefoon']; ?>" <?php checked("1", $this->settings['telefoon'])?>></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['huisadres'])) $this->settings['huisadres'] = ''; ?>
									<td>Niet bij buren bezorgen (+ &euro; 0.23)</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][huisadres]" value="x" <?php checked("1", $this->settings['huisadres'])?>></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['handtekening'])) $this->settings['handtekening'] = ''; ?>
									<td>Handtekening voor ontvangst (+ &euro; 0.30)</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][handtekening]" value="x" <?php checked("1", $this->settings['handtekening'])?>></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['huishand'])) $this->settings['huishand'] = ''; ?>
									<td>Niet bij buren bezorgen + Handtekening voor ontvangst (+ &euro; 0.37)</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][huishand]" value="x" <?php checked("1", $this->settings['huishand'])?>></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['retourbgg'])) $this->settings['retourbgg'] = ''; ?>
									<td>Retour bij geen gehoor</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][retourbgg]" value="x" <?php checked("1", $this->settings['retourbgg'])?>></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['verzekerd'])) $this->settings['verzekerd'] = ''; ?>
									<td>Verhoogd aansprakelijk (+ &euro; 1.50 per &euro; 500 verzekerd)</td>
									<td><input type="checkbox" name="data[<?php echo $row['orderid']; ?>][verzekerd]" value="x" <?php checked("1", $this->settings['verzekerd'])?>></td>
								<tr>
									<td>Verzekerd bedrag (afgerond in hele in &euro;)</td>
									<td><input type="text" name="data[<?php echo $row['orderid']; ?>][verzekerdbedrag]" value="" size="5"></td>						
								</tr>
								<tr>
									<?php if (!isset($this->settings['bericht'])) $this->settings['bericht'] = '';
									$bericht = str_replace('[ORDER_NR]', $row['ordernr'], $this->settings['bericht']);
									?>
									<td>Optioneel bericht (niet op label, wel in track&trace)</td>
									<td><input type="text" name="data[<?php echo $row['orderid']; ?>][bericht]" value="<?php echo $bericht; ?>"></td>
								</tr>
								<tr>
									<?php if (!isset($this->settings['kenmerk'])) $this->settings['kenmerk'] = '';
									$kenmerk = str_replace('[ORDER_NR]', $row['ordernr'], $this->settings['kenmerk']);
									?>
									<td>Eigen kenmerk (linksboven op label)</td>
									<td><input type="text" name="data[<?php echo $row['orderid']; ?>][kenmerk]" value="<?php echo $kenmerk; ?>"></td>
								</tr>
							</table>
						</td>
					</tr>
					<?php } // end else ?>
				</table>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
	</table>
<input type="hidden" name="action" value="wcflespakket-export">
<div class="submit-wcflespakket">
	<input type="submit" value="Exporteer naar Flespakket" class="button-wcflespakket">
	<img src="<?php echo dirname(plugin_dir_url(__FILE__)).'/img/wpspin_light.gif';?>" class="waiting"/>
</div>
</form>
<script type="text/javascript">
	jQuery(document).ready(function($){
		$('.button-wcflespakket').click(function(){
			$('.waiting').show();
		});

		//let the appropiate amount of 'soort zending' selects appear when 'aantal colli' is changed
		$("select.colli-amount").change(function(){
			var multicolli = parseInt($(this).val());

			if( multicolli > 1 ) {
				$(this).closest('tr').next('tr').find('.collo_1_label').html('Soort zending 1');
			} else {
				$(this).closest('tr').next('tr').find('.collo_1_label').html('Soort zending');
			}

			order_id = $(this).attr("data-orderid");

			for(i=2; i<(multicolli)+1; i++){
				var package_select = "select#"+order_id+"_package_"+(i);
				$(package_select).closest('tr').show();//show the amount of extra 'soort zending' needed
				// $((package_select)).addClass('required');
			}
			for(i=multicolli+1; i<11; i++){
				var package_select = "select#"+order_id+"_package_"+(i);
				$(package_select).closest('tr').hide();//hide the rest
				// $(package_select).val(null);//and empty the field
			}
		});
	});
</script>

</body>
</html>
