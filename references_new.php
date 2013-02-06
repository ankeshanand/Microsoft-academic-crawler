<?php
//connecting to Database
mysql_connect("localhost", "root") or die(mysql_error());
//echo "Connected to MySQL<br />";
mysql_select_db("project") or die(mysql_error());
echo "Connected to Databasy";
$filename=$_GET['table'].".txt";
//echo $filename;


$table_name=$_GET['table'];

$result = mysql_query("SELECT * FROM $table_name WHERE done=0");
while($row = mysql_fetch_array($result))
{
	file_put_contents("temp2.txt", "");
	unlink("temp2.txt");

	$sp = fopen($filename, 'r');
	$op = fopen("temp2.txt", 'w');

	while (!feof($sp)) {
		$buffer = fread($sp, 512);  // use a buffer of 512 bytes
		fwrite($op, $buffer);
	}

	$id = $row['ID'];
	$title=$row['Title']; 
	// 1. initialize
	$ch = curl_init();

	// 2. set the options, including the url
	curl_setopt($ch, CURLOPT_URL, "http://academic.research.microsoft.com/Publication/$id");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);

	$userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 (.NET CLR 3.5.30729)';
	curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	// 3. execute and fetch the resulting HTML output
	$html = curl_exec($ch);
	//echo $html;
	if($html)
	{
		//echo "HERE";
		mysql_query("UPDATE $table_name SET done=1 WHERE ID='$id'") or die(mysql_error);
		//$result2=mysql_query($query);
	}
	$dom = new DOMDocument();
	@$dom->loadHTML($html);
	$info = curl_getinfo($ch);
	//echo 'Took ' . $info['total_time'] . ' seconds for url ' . $info['url'];

	// grab all the on the page
	$xpath = new DOMXPath($dom);
	//echo $xpath;


	$authors=array();
	$hrefs = $xpath->evaluate("//div[@class='paper-info']//a[@class='author-name-tooltip']");
	//echo '<br>'.$hrefs->length.'<br>';
	for ($i = 0; $i < $hrefs->length; $i++) {
		$href = $hrefs->item($i);
		//echo ($href->nodeValue).'<br>';
		$authors[$i]=$href->nodeValue;
	}
	//print_r($authors);
	$hrefs = $xpath->evaluate("//div[@class='abstract']//span");
	//echo '<br>' . $hrefs->length . '<br>';

	for ($i = 0; $i < $hrefs->length; $i++) {
		$href = $hrefs->item($i);
		$abstract = $href->nodeValue;
		//echo $abstract;
	}

	//print_r(authors);
	$hrefs = $xpath->evaluate("//div[@class='paper-info']//span[@class='year']");
	//echo '<br>'.$hrefs->length.'<br>';
	for ($i = 0; $i < $hrefs->length; $i++) {
		$href = $hrefs->item($i);
		$value = $href->nodeValue;
		//echo $value;
		preg_match("/\d{4}$/",$value,$output);
		//print_r($output);
		$year= $output[0];
		//echo $year. '<br>';
		if(($year>1800)&&($year<2020))
		{
			break;
		}
	}

	curl_setopt($ch, CURLOPT_URL, "http://academic.research.microsoft.com/Detail?entitytype=1&searchtype=2&id=$id&start=1&end=50");
	$html = curl_exec($ch);
	//echo $html;
	$dom = new DOMDocument();
	@$dom->loadHTML($html);
	$info = curl_getinfo($ch);
	//echo 'Took ' . $info['total_time'] . ' seconds for url ' . $info['url'];

	// grab all the on the page
	$xpath = new DOMXPath($dom);
	//echo $xpath;
	$hrefs = $xpath->evaluate("//div[@class='title-download']//h3/a");
	//echo '<br>' . $hrefs->length . '<br>';
	$references=array();

	for ($i = 0; $i < $hrefs->length; $i++) {
		$href = $hrefs->item($i);
		$number = $href->getAttribute('href') ;
		//echo $number;
		preg_match("/[0-9]+/",$number,$matches);
		$num= $matches[0];
		//echo $num.'<br>';
		$references[$i]=$num;

	}

	//echo count($references);
	//  $op = fopen('data.txt', 'w') or die("can't open file");

	fwrite($op,'#*');
	fwrite($op, $title);
	fwrite($op, "\n");

	for($i=0;$i<count($authors);$i++)
	{
		fwrite($op,'#@');
		fwrite($op, $authors[$i]);
		fwrite($op, "\n");
	}

	fwrite($op,'#t');
	fwrite($op, $year);
	fwrite($op, "\n");

	fwrite($op,'#index00');
	fwrite($op, $id);
	fwrite($op, "\n");

	for($i=0;$i<count($references);$i++)
	{
		fwrite($op,'#!');
		fwrite($op, $references[$i]);
		fwrite($op, "\n");
	}

	fwrite($op,'#t');
	fwrite($op, $abstract);
	fwrite($op, "\n");


	fwrite($op, "\n");

	// close handles
	fclose($op);
	fclose($sp);

	// make temporary file the new source
	rename("temp2.txt", $filename);
}
?>











