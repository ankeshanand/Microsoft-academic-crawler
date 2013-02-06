<?php
//include("../config.inc.php");
//connecting to Database
mysql_connect("localhost", "root") or die(mysql_error());
//echo "Connected to MySQL<br />";
mysql_select_db("project") or die(mysql_error());
echo "Connected to Database";

// 1. initialize
$ch = curl_init();

// 2. set the options, including the url
$start=1;
$end=100;
$domain=$_GET['subdomain'];
$field_table=$_GET['table'];
//echo $field_table;
$field=rawurlencode($domain);

curl_setopt($ch, CURLOPT_URL, "http://academic.research.microsoft.com/Search?query=$field&start=$start&end=$end");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);

$userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 (.NET CLR 3.5.30729)';
curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
// 3. execute and fetch the resulting HTML output
$html = curl_exec($ch);
if (!$html) {
	echo "<br />cURL error number:" .curl_errno($ch);
	echo "<br />cURL error:" . curl_error($ch);
	exit;
}
//echo $html;
$dom = new DOMDocument();
@$dom->loadHTML($html);
$info = curl_getinfo($ch);
echo 'Took ' . $info['total_time'] . ' seconds for url ' . $info['url'];

// grab all the on the page
$xpath = new DOMXPath($dom);
//echo $xpath;
$hrefs = $xpath->evaluate("//div[@class='title']//h2/a");
//echo '<br>' . $hrefs->length . '<br>';

for ($i = 0; $i < $hrefs->length; $i++) {
	$href = $hrefs->item($i);
	$number = $href->nodeValue  ;
	preg_match("/[0-9]+/",$number,$output);
	$num= $output[0];
//	echo $num;
	
}
if($num==0)
{
	$num=100000;
}

while($start<=$num)
{
curl_setopt($ch, CURLOPT_URL, "http://academic.research.microsoft.com/Search?query=$field&SearchDomain=2&start=$start&end=$end");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);

$userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 (.NET CLR 3.5.30729)';
curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
// 3. execute and fetch the resulting HTML output
$html = curl_exec($ch);
//echo $html;
$dom = new DOMDocument();
@$dom->loadHTML($html);
$info = curl_getinfo($ch);
//echo 'Took ' . $info['total_time'] . ' seconds for url ' . $info['url'];

// grab all the on the page
$xpath = new DOMXPath($dom);
$hrefs = $xpath->evaluate("//div[@class='title-download']//h3/a");
//echo '<br>' . $hrefs->length . '<br>';
for ($i = 0; $i < $hrefs->length; $i++) {
	$href = $hrefs->item($i);
	$name = $href->nodeValue  ;
	$link= $href->getAttribute('href');
	preg_match("/[0-9]+/",$link,$matches);
	$id= $matches[0];

	mysql_query("INSERT INTO $field_table VALUES ('$name',$id)");
	//storeLink($url,$target_url);
	//echo "<br />Link stored: $url";
}
$start=$start+100;
$end=$end+100;
}

// 4. free up the curl handle
curl_close($ch);
?>
