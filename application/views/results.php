<!DOCTYPE html>
<html>
<head>
	<title><?php if($mode == 'votes') { echo $ward_data['ward_name'] . ' | ' . $ward_data['district_name']; ?> | <?php } else if ($mode == 'wards') { echo $wards[0]['district_name'] . ' | '; } ?>London Mayoral Election 2008 Votes</title>
	<link type="text/css" rel="stylesheet" href="<?php echo base_url(); ?>assets/css/reset.css" />
	<link type="text/css" rel="stylesheet" href="<?php echo base_url(); ?>assets/css/grid.css" />
	<link type="text/css" rel="stylesheet" href="<?php echo base_url(); ?>assets/css/style.css?v=2" />
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

	<script>
		$(document).ready(function(){
			$('#see-districts').click(function(){
				$('ul.tags').slideToggle();
			});
		});
	</script>

	<?php if($mode == 'votes') { ?>
		<script type="text/javascript">
	      google.load("visualization", "1", {packages:["corechart"]});
	      google.setOnLoadCallback(drawChart);
	      function drawChart() {
	        var data = new google.visualization.DataTable();
	      	
	      	<?php if($graphMode == 'single') { ?>
		        data.addColumn('string', 'Candidate & Party');
		        data.addColumn('number', 'Votes');
		        data.addRows([
		        	<?php $i=0; foreach($votes as $v) { ?>
		        		<?php if($v['cat_id'] == '1') { ?>
		        			['<?php echo addslashes($v['candidate_name'] . ' (' . $v['party_name'] . ')'); ?>', <?php echo $v['votes']; ?>],
		        		<?php } ?>
		        	<?php $i++; } ?>
		        ]);

		        var options = {
		          backgroundColor: '#efefef',
		          is3D: true,
		  		  chartArea: {
		  		  	left: 20,
		  		  	top: 20,
		  		  	width: 700,
		  		  	height: 400
		  		  },
		          pieSliceText: 'value',
		          sliceVisibilityThreshold: 1/1000,
		          slices:
		          	<?php $str_arr = array(); 
		          	foreach($votes as $i=>$v) {
		          		if($v['colour'] != '') {
		          			$str_arr[] = $i . ": {color: '" . $v['colour'] . "'}";
	          			}
	          		} 
	          		$str = implode(", ", $str_arr);
	          		echo '{' . $str . '}';
	          		?>
		        };
		       	var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
		        
	        <?php } else if ($graphMode == 'dual') { ?>
				data.addColumn('string', 'Candidate & Party');
			    data.addColumn('number', "This ward's votes");
			    data.addColumn('number', "Average London votes");
			    data.addRows([
				    <?php $i=0; foreach($votes as $v) { ?>
		        		<?php if($v['cat_id'] == '1') { ?>
				        	['<?php echo addslashes($v['candidate_name'] . ' (' . $v['party_name'] . ')'); ?>', 
				        	<?php echo $v['votes']; ?>,
				        	<?php echo round($overall_votes[$v['candidate_id']]); ?>],
			        	<?php } ?>
				    <?php } ?>
			    ]);

			    var options = {
			      	pointSize: 3,
		    	  	focusTarget: 'category',
		          	backgroundColor: '#efefef',
		          	legend: 'top',
		          	hAxis: {		
						textPosition: 'out',
						viewWindowMode: 'pretty'
					},
		  		  	chartArea: {
		  		  		left: 100,
		  		  		top: 20,
		  		  		width: 600,
		  		  		height: 400
		  		  	},
		  		  	series: {
	                	0: {color: '#79bd8f'}, 
	                	1: {color: '#beeb9f'}
	                }
	        	};
	        	var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
			<?php } ?>
	        chart.draw(data, options);
	      }
	    </script>
	<?php } ?>

	<script type="text/javascript">

	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', 'UA-96842-11']);
	  _gaq.push(['_trackPageview']);

	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();

	</script>

</head>
<body>

	<div id="container" class="fourteen-col">
		
		<a href="<?php echo site_url(); ?>"><img class="logo" src="<?php echo base_url(); ?>assets/img/logo2.png" alt="Who voted what?" /></a>

		<div class="main">

			<?php if($mode == 'choose') { ?>

				<p class="intro">Want to know if anyone voted BNP on your street? Curious to see if your area of London is more Tory than Labour? Or just wondering if you're the only Green voter in your district? Here's the data, in handy graph form so you don't have to think. Includes data from 2008 and 2012.</p>

				<form method="post" class="choosy" action="<?php echo site_url('election'); ?>">
					<label>See voting stats for</label>
					<input type="text" name="postcode" placeholder="your postcode" />
					<select name="year">
						<?php foreach($this->years as $y) { ?>
							<option value="<?php echo $y; ?>"><?php echo $y; ?></option>
						<?php } ?>
					</select>
					<input type="submit" class="btn" value="&raquo;" />
				</form>
				<p class="prefix">(enter postcodes in the format 'SW4 9JP' -- London only, obviously)</p>

				<div class="hr"><span>or</span></div>

				<h2 class="all"><a href="javascript://" id="see-districts">See voting stats for all London regions</a></h2>

				<ul class="tags initially-off">
					<?php foreach($districts as $d) { ?>
						<li><a href="<?php echo site_url('london/2012/' . url_title($d['district_name'], 'dash', TRUE)); ?>"><?php echo $d['district_name']; ?></a></li>
					<?php } ?>
				</ul>

				<div class="hr"><span>and finally</span></div>

				<h2>Some interesting sample queries</h2>
				<p class="prefix">Which ward had...</p>
				<ul class="tags">
					<li><a href="<?php echo site_url('london/2012/stats/most-bnp'); ?>">The most BNP voters</a></li>
					<li><a href="<?php echo site_url('london/2012/stats/most-tory'); ?>">The most Tory voters</a></li>
					<li><a href="<?php echo site_url('london/2012/stats/most-labour'); ?>">The most Labour voters</a></li>
					<li><a href="<?php echo site_url('london/2012/stats/most-green'); ?>">The most Green voters</a></li>
					<li><a href="<?php echo site_url('london/2012/stats/least-bnp'); ?>">The least BNP voters</a></li>
					<li><a href="<?php echo site_url('london/2012/stats/least-tory'); ?>">The least Tory voters</a></li>
					<li><a href="<?php echo site_url('london/2012/stats/least-labour'); ?>">The least Labour voters</a></li>
					<li><a href="<?php echo site_url('london/2012/stats/least-green'); ?>">The least Green voters</a></li>
				</ul>

			<?php } else if ($mode == 'error') { ?> 


				<h2>Not found</h2>
				<h3>No voting data found for this election / ward</h3>

			<?php } else if ($mode == 'wards') { ?>
				<h2><?php echo $wards[0]['district_name']; ?></h2>
				<h3>Comprising <?php echo count($wards); ?> wards</h3>

				<ul class="tags">
					<?php foreach($wards as $w) { ?>
						<li><a href="<?php echo site_url('london/2012/' . url_title($w['district_name'], 'dash', TRUE) . '/' . $w['new_code']); ?>"><?php echo $w['ward_name']; ?></a></li>
					<?php } ?>
				</ul>

			<?php } else if ($mode == 'votes') { ?>

				<div class="six-col">
					<h2><?php echo $ward_data['ward_name']; ?></h2>
					<h3><?php echo $ward_data['district_name']; ?></h3>
				</div>
				<div class="six-col edge">
					<p class="intro results">Highest scoring candidate for this ward <br /><strong><?php echo $winner['candidate'] . ' (' . $winner['party_name'] .')'; ?></strong></p>
				</div>

				<div class="badges">

					<?php
						$diff['BNP'] = array('diff' => $votes_candidates[$badges['bnp']] / $overall_votes[$badges['bnp']] * 100, 'class' => 'bnp');
						$diff['Tory Party'] = array('diff' => $votes_candidates[$badges['tory']] / $overall_votes[$badges['tory']] * 100, 'class' => 'tories');
						$diff['Labour Party'] = array('diff' => $votes_candidates[$badges['labour']] / $overall_votes[$badges['labour']] * 100, 'class' => 'labour');
						$diff['Green Party'] = array('diff' => $votes_candidates[$badges['green']] / $overall_votes[$badges['green']] * 100, 'class' => 'greens');

						$badge_html = '';
						foreach($diff as $party=>$d) {
							if($d['diff'] < $bounds['lower']) {
								$badge_html .= '<span title="This ward is below average for ' . $party . ' votes ('.round($d['diff']) . '%)" class="badge avg_down ' . $d['class'] . '">'. $party . ' -' . round($d['diff']) . '% <b></b></span>';
							} else if ($d['diff'] > $bounds['upper']) {
								$badge_html .= '<span title="This ward is above average for ' . $party . ' votes ('.round($d['diff']) . '%)" class="badge avg_up ' . $d['class'] . '">'. $party . ' +' . round($d['diff']) . '% <b></b></span>';
							}
						}

						if ($badge_html != '') {
							echo '<div class="hr"><span>Results for ' . $year . '</span></div>';
							echo '<span>Badges for this ward: </span>';
							echo $badge_html;
						}
					?>
				</div>

				<div class="twelve-col">
					<?php if ($graphMode == 'single') { ?>
						<div id="chart_div" style="width: 700px; height: 400px;"></div>
					<?php } else if ($graphMode == 'dual') { ?>
						<div id="chart_div" style="width: 700px; height: 500px; margin-bottom: 20px;"></div>
					<?php } ?>
				</div>

				<div class="share">
					<a href="https://twitter.com/share" class="twitter-share-button" data-lang="en" data-url="<?php echo current_url(); ?>" data-related="mattpointblank" data-text="London Mayoral Election <?php echo $year; ?> vote stats for <?php echo $ward_data['ward_name']; ?>" data-hashtags="whovotedwhat" data-size="large">Tweet</a>
					<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
				</div>

				<div id="disqus_thread"></div>
				<script type="text/javascript">
				    var disqus_shortname = 'whovotedwhat'; 
				    (function() {
				        var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
				        dsq.src = 'http://' + disqus_shortname + '.disqus.com/embed.js';
				        (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
				    })();
				</script>

				<h2>Votes data</h2>
				<h3><?php echo $votes_totals[1]['votes']; ?> first preference votes across <?php echo $votes_totals[1]['candidates']; ?> candidates</h3>

				<table cellspacing="0" cellpadding="0">
					<thead>
						<tr>
							<th>Candidate Name / Party</th>
							<th>Number of first preference votes</th>
							<th>Average votes across all wards</th>
							<th>Statistically significant difference?</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($votes as $v) { ?>
							<?php if($v['cat_id'] == '1') { ?>
								<tr>
									<td><?php echo '<strong>' . $v['candidate_name'] . '</strong><br />' . $v['party_name']; ?></td>
									<td><?php echo $v['votes']; ?></td>
									<td><?php echo round($overall_votes[$v['candidate_id']]); ?></td>
									<td><?php $diff = $v['votes'] / $overall_votes[$v['candidate_id']] * 100; 
									if($diff < $bounds['lower']) { echo '<span class="avg_down">Below average (' . round($diff) . '%)</span>'; } else if ($diff > $bounds['upper']) { echo '<span class="avg_up">Above average (' . round($diff) . '%)</span>'; } else { echo 'Average'; } ?></td>
								</tr>
							<?php } ?>
						<?php } ?>
					</tbody>
				</table>

			<?php } ?>

			<div class="hr"><span>Credits</span></div>
			<p>This tool is a hack by by <a href="http://mattandrews.info">Matt Andrews</a>. I'm a web developer, not a statistician. Don't sue me.</p>

			<p>Source data taken from London Elects' Mayoral Election <a href="https://docs.google.com/a/guardian.co.uk/spreadsheet/ccc?key=0AonYZs4MzlZbdDhhQWE5RzFVcTlSRXRQN3REN1ZPNEE#gid=1">2008</a> / <a href="https://docs.google.com/a/guardian.co.uk/spreadsheet/ccc?key=0AonYZs4MzlZbdHdFUU92YU1GMGNfc0pTbnAxMF81T2c#gid=0">2012</a> data and Chris Bell's supremely useful <a href="http://www.doogal.co.uk/UKPostcodes.php">UK Postcodes data</a>.</p>


		</div>

	</div>

</body>
</html>