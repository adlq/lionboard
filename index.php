<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="style.css">
	<title>Localisation Dashboard</title>
	<link href="images/favicon.ico" rel="icon" type="image/x-icon" />
</head>
<body>
<?php
require_once('../ZanataPHPToolkit.php');
require_once('config.php');
require_once('../conf.php');

$zanataUrl = $GLOBALS['conf']['zanata']['url'];
$user = $GLOBALS['conf']['zanata']['user'];
$apiKey = $GLOBALS['conf']['zanata']['apiKey'];

if (!$DASHBOARD['config']['verbose'])
{
	$projectNum = 0;

	// Count the projects
	foreach ($configs as $key => $config)
	{
		if ($key !== 'Zanata')
			$projectNum++;
	}

	// CruiseControl display
	$blockHeight = "style='height: " . 100 / $projectNum . "%'";
}
else 
{
	$blockHeight = '';
}

foreach ($GLOBALS['conf'] as $key => $config)
{
	if ($key !== 'zanata')
	{
		$projectSlug = $config['projectSlug'];
		$iterationSlug = $config['iterationSlug'];	
		
		$zanataToolkit = new ZanataPHPToolkit($user, $apiKey, $projectSlug, $iterationSlug, $zanataUrl);
		
		// Retrieve project name
		$rawStats = json_decode($zanataToolkit->getZanataCurlRequest()->getProject($projectSlug));
		
		if (empty($rawStats))
			exit(1);
		
		$projectName = $rawStats->name;
		
		echo "<div class='block' $blockHeight><h1>$projectName - $iterationSlug</h1>";
		
		$stats = $zanataToolkit->getTranslationStats();
		
		// POT file download link
		$potFileLink = $zanataToolkit->getZanataCurlRequest()->getZanataApiUrl()->fileService($projectSlug, $iterationSlug, 'pot', $key);
		
		// Update history
		$updateHistory = htmlspecialchars(file_get_contents("/home/nduong/l10n/log/$key.log"));
		$logLines = split("\n", trim($updateHistory));
		$lastUpdated = array();
		preg_match("/\[(.+)\]/", $logLines[0], $lastUpdated);
		$timeDiff = date('i \m\i\n\u\t\e\s s \s\e\c\o\n\d\s', time() - strtotime($lastUpdated[1]));
		$lastUpdatedMsg = empty($lastUpdated) ? '' : "(last updated $timeDiff ago)";

		if (!empty($stats))
		{
			$total = $stats[key($stats)]['total'];
			
			$details = '';
			if ($DASHBOARD['config']['verbose'])
			{
				$details = <<<DET
				<a href="$potFileLink">Get POT file</a>
			</p>
			<div>
				<h3>Update log $lastUpdatedMsg</h3>
				<pre class="log">$updateHistory</pre>
			</div>
DET;
			}
			
			echo <<<ECHO
			<div class="topRow">
			<p>
			Total number of strings: <span class="totalText">$total</span><br>
			$details
ECHO;
			
			foreach ($stats as $locale => $stat)
			{
				$translated = $stat['translated'];
				$needReview = $stat['needReview'];
				$untranslated = $stat['untranslated'];
				
				// Compute progress bar stuff
				$totalSize = 70;
				$translatedSize = $translated * $totalSize / $total;
				$needReviewSize = $needReview * $totalSize / $total;
				$untranslatedSize = $untranslated * $totalSize / $total;
				
				// Flag
				$flagName = strtolower(substr($locale, 0, 2));
				
				// PO file link
				$poFileLink = $zanataToolkit->getZanataCurlRequest()->getZanataApiUrl()->fileService($projectSlug, $iterationSlug, 'po', $key, $locale);
				
				if ($DASHBOARD['config']['verbose'])
				{
//					echo '</div>';
					echo <<<ECHO
<div class="row">
						<h3>$locale <img src="images/flags/$flagName.gif"/></h3>
						<div class="translated" style="width:{$translatedSize}%"></div>
						<div class="needReview" style="width:{$needReviewSize}%"></div>
						<div class="untranslated" style="width:{$untranslatedSize}%"></div>
						<br><br>
						<ul>
							<li>Translated: <span class="translatedText">$translated</span></li>
							<li>Need review: <span class="needReviewText">$needReview</span></li>
							<li>Untranslated: <span class="untranslatedText">$untranslated</span></li>
							<li>Last translated: $stat[lastTranslated]</li>
						</ul>
						<div class="button"><a href="$poFileLink">Get PO file</a></div>
					</div>
ECHO;
				}
				else 
				{
					echo <<<ECHO
					<div class="inlineEl">
						$locale <img src="images/flags/$flagName.gif"/><br>
						<div class="translated" style="width:{$translatedSize}%"></div>
						<div class="needReview" style="width:{$needReviewSize}%"></div>
						<div class="untranslated" style="width:{$untranslatedSize}%"></div>
					</div>
ECHO;
				}
			}
			
			echo "</div>";
		}
		echo "</div>";
	}
}
?>
<script src="script.js"></script>
</body>
</html>