<?php
/**
 * f_util_StringUtils test case.
 */
class f_util_StringUtilsTest extends testing_UnitTestBase
{
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp($this->getName());
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->f_util_StringUtils = null;
		
		parent::tearDown();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct()
	{
	}
	
	/**
	 * Tests f_util_StringUtils->convertEncoding()
	 * @see f_util_StringUtils::convertEncoding
	 */
	public function testConvertEncoding()
	{
		$stringUtf8 = "éèà";
		$stringIso = mb_convert_encoding($stringUtf8, "ISO-8859-1", "UTF-8");
		$this->assertEquals($stringUtf8, f_util_StringUtils::convertEncoding($stringIso, "ISO-8859-1"));
		$this->assertEquals($stringIso, f_util_StringUtils::convertEncoding($stringUtf8, "UTF-8", "ISO-8859-1"));
	}
	
	/**
	 * Tests f_util_StringUtils->utf8Encode()
	 * @see f_util_StringUtils::utf8Encode
	 */
	public function testUtf8Encode()
	{
		$stringUtf8 = "éèà";
		$stringIso = mb_convert_encoding($stringUtf8, "ISO-8859-1", "UTF-8");
		$this->assertEquals($stringUtf8, f_util_StringUtils::convertEncoding($stringIso, "ISO-8859-1"));
		$this->assertEquals($stringUtf8, f_util_StringUtils::convertEncoding($stringIso));
	}
	
	/**
	 * Tests f_util_StringUtils->endsWith()
	 * @see f_util_StringUtils::endsWith
	 */
	public function testEndsWith()
	{
		$haystack = "My string end with this Text";
		$needle = "this text";
		
		$this->assertTrue(f_util_StringUtils::endsWith($haystack, $needle, f_util_StringUtils::CASE_INSENSITIVE));
		$this->assertFalse(f_util_StringUtils::endsWith($haystack, $needle, f_util_StringUtils::CASE_SENSITIVE));
		
		$needle = "this Text";
		$this->assertTrue(f_util_StringUtils::endsWith($haystack, $needle, f_util_StringUtils::CASE_SENSITIVE));
		
		$needle = "not this";
		$this->assertFalse(f_util_StringUtils::endsWith($haystack, $needle, f_util_StringUtils::CASE_INSENSITIVE));
	}
	
	/**
	 * Tests f_util_StringUtils->beginsWith()
	 * @see f_util_StringUtils::beginsWith
	 */
	public function testBeginsWith()
	{
		$haystack = "My string end with this Text";
		$needle = "my string";
		
		$this->assertTrue(f_util_StringUtils::beginsWith($haystack, $needle, f_util_StringUtils::CASE_INSENSITIVE));
		$this->assertFalse(f_util_StringUtils::beginsWith($haystack, $needle, f_util_StringUtils::CASE_SENSITIVE));
		
		$needle = "My string";
		$this->assertTrue(f_util_StringUtils::beginsWith($haystack, $needle, f_util_StringUtils::CASE_SENSITIVE));
		
		$needle = "not my string";
		$this->assertFalse(f_util_StringUtils::beginsWith($haystack, $needle, f_util_StringUtils::CASE_INSENSITIVE));
	}
	
	/**
	 * Tests f_util_StringUtils->contains()
	 * @see f_util_StringUtils::contains
	 */
	public function testContains()
	{
		$str = "My string end with this Text";
		$needle = "my string";
		$this->assertFalse(f_util_StringUtils::contains($str, $needle));
		
		$needle = "end with ";
		$this->assertTrue(f_util_StringUtils::contains($str, $needle));
		
		$needle = "this Text";
		$this->assertTrue(f_util_StringUtils::contains($str, $needle));
		
		$needle = "no no no";
		$this->assertFalse(f_util_StringUtils::contains($str, $needle));
	}
	
	/**
	 * Tests f_util_StringUtils->caseToUnderscore()
	 * @see f_util_StringUtils::caseToUnderscore
	 */
	public function testCaseToUnderscore()
	{
		$str = "StringToBeSeparateByUnderscore";
		$this->assertEquals("string_to_be_separate_by_underscore", f_util_StringUtils::caseToUnderscore($str));
		
		$str = "stringToBeSeparateByUnderscore";
		$this->assertEquals("string_to_be_separate_by_underscore", f_util_StringUtils::caseToUnderscore($str));
	}
	
	/**
	 * Tests f_util_StringUtils->underscoreToCase()
	 * @see f_util_StringUtils::underscoreToCase
	 */
	public function testUnderscoreToCase()
	{
		$str = "string_to_be_separate_by_underscore";
		$this->assertEquals("StringToBeSeparateByUnderscore", f_util_StringUtils::underscoreToCase($str));
		
		$str = "string_to_be_separate_by_underscore";
		$this->assertNotEquals("stringToBeSeparateByUnderscore", f_util_StringUtils::underscoreToCase($str));
	}
	
	/**
	 * Tests f_util_StringUtils->doTranscode()
	 * @see f_util_StringUtils::doTranscode
	 */
	public function testDoTranscode()
	{
		
		$array = array('a', 'bc', 'def');
		$this->assertEquals(array('a', 'bc', 'def'), f_util_StringUtils::doTranscode($array));
		
		$string = "abcd efgh";
		$this->assertEquals("abcd efgh", f_util_StringUtils::doTranscode($string));
		
		$int = 1;
		$this->assertEquals(1, f_util_StringUtils::doTranscode($int));
	
	}
	
	/**
	 * Tests f_util_StringUtils->transcodeStringsInArray()
	 * @see f_util_StringUtils::transcodeStringsInArray
	 */
	public function testTranscodeStringsInArray()
	{
		$this->markTestSkipped("Tested in testDoTranscode");
	}
	
	/**
	 * Tests f_util_StringUtils->transcodeString()
	 * @see f_util_StringUtils::transcodeString
	 */
	public function testTranscodeString()
	{
		$this->markTestSkipped("Tested in testDoTranscode");
	}
	
	/**
	 * Tests f_util_StringUtils->randomString()
	 * @see f_util_StringUtils::randomString
	 */
	public function testRandomString()
	{
		$string = f_util_StringUtils::randomString();
		$this->assertEquals(8, strlen($string));
		
		$string = f_util_StringUtils::randomString(10);
		$this->assertEquals(10, strlen($string));
		
		$string = f_util_StringUtils::randomString(12);
		$this->assertNotEquals(strtolower($string), $string);
		
		$string = f_util_StringUtils::randomString(12, false);
		$this->assertEquals(strtolower($string), $string);
	}
	
	/**
	 * Tests f_util_StringUtils->isHexa()
	 * @see f_util_StringUtils::isHexa
	 */
	public function testIsHexa()
	{
		$this->assertFalse(f_util_StringUtils::isHexa());
		
		$this->assertFalse(f_util_StringUtils::isHexa("not hexa"));
		
		$this->assertFalse(f_util_StringUtils::isHexa(0));
		
		$this->assertTrue(f_util_StringUtils::isHexa("0"));
		
		$this->assertFalse(f_util_StringUtils::isHexa(0x0ffa10));
		
		$this->assertTrue(f_util_StringUtils::isHexa("0ffa10"));
	}
	
	/**
	 * Tests f_util_StringUtils->parseAssocString()
	 * @see f_util_StringUtils::parseAssocString
	 */
	public function testParseAssocString()
	{
		$this->assertEquals(array(), f_util_StringUtils::parseAssocString());
		
		$string = "name1: value1; name2: value2;";
		$array = array(0 => "name1: value1; name2: value2;", "name1" => "value1", "name2" => "value2");
		$this->assertEquals($array, f_util_StringUtils::parseAssocString($string));
		
		$string = "name1: value1; name2: value2";
		$array = array(0 => "name1: value1; name2: value2", "name1" => "value1", "name2" => "value2");
		$this->assertEquals($array, f_util_StringUtils::parseAssocString($string));
		
		$string = "name1:value1;name2:value2;:value3";
		$array = array(0 => "name1:value1;name2:value2;:value3", "name1" => "value1", "name2" => "value2", "" => "value3");
		$this->assertNotEquals($array, f_util_StringUtils::parseAssocString($string));
		
		$array = array(0 => "name1:value1;name2:value2;:value3", "name1" => "value1", "name2" => "value2");
		$this->assertEquals($array, f_util_StringUtils::parseAssocString($string));
	}
	
	/**
	 * Tests f_util_StringUtils->shortenString()
	 * @see f_util_StringUtils::shortenString
	 */
	public function testShortenString()
	{
		$string = "Pellentesque nisl urna, lacinia eget, sagittis nec, pharetra eu, lectus? Proin pellentesque, ante at sodales egestas; pede est laoreet quam, a posuere lorem diam id neque.Pellentesque nisl urna, lacinia eget, sagittis nec, pharetra eu, lectus? Proin pellentesque, ante at sodales egestas; pede est laoreet quam, a posuere lorem diam id neque.";
		
		$shortenString = f_util_StringUtils::shortenString($string);
		$this->assertEquals(strlen($shortenString), f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING_DEAFULT_MAX_LENGTH);
		$this->assertEquals($shortenString, "Pellentesque nisl urna, lacinia eget, sagittis nec, pharetra eu, lectus? Proin pellentesque, ante at sodales egestas; pede est laoreet quam, a posuere lorem diam id neque.Pellentesque nisl urna, lacinia eget, sagittis nec, pharetra eu, lectus? Proin pe...");
		
		$shortenString = f_util_StringUtils::shortenString($string, 10);
		$this->assertEquals(strlen($shortenString), 10);
		$this->assertEquals($shortenString, "Pellent...");
		
		$shortenString = f_util_StringUtils::shortenString($string, 20, "..##..");
		$this->assertEquals(strlen($shortenString), 20);
		$this->assertEquals($shortenString, "Pellentesque n..##..");
		
		$shortenString = f_util_StringUtils::shortenString(null);
		$this->assertEquals(null, $shortenString);
	}
	
	/**
	 * Tests f_util_StringUtils->highlightString()
	 * @see f_util_StringUtils::highlightString
	 */
	public function testHighlightString()
	{
		$string = "Pellentesque nisl urna, lacinia eget, sagittis nec, pharetra eu, lectus? Proin pellentesque, ante at sodales egestas; pede est laoreet quam, nec a posuere lorem diam id neque.";
		
		$highlight = "pharetra";
		$this->assertEquals("Pellentesque nisl urna, lacinia eget, sagittis nec, <strong>pharetra</strong> eu, lectus? Proin pellentesque, ante at sodales egestas; pede est laoreet quam, nec a posuere lorem diam id neque.", f_util_StringUtils::highlightString($string, $highlight));
		
		$highlight = "nec";
		$this->assertEquals("Pellentesque nisl urna, lacinia eget, sagittis <strong>nec</strong>, pharetra eu, lectus? Proin pellentesque, ante at sodales egestas; pede est laoreet quam, <strong>nec</strong> a posuere lorem diam id neque.", f_util_StringUtils::highlightString($string, $highlight));
		
		$highlight = array("pharetra", "nec");
		$this->assertEquals("Pellentesque nisl urna, lacinia eget, sagittis <strong>nec</strong>, <strong>pharetra</strong> eu, lectus? Proin pellentesque, ante at sodales egestas; pede est laoreet quam, <strong>nec</strong> a posuere lorem diam id neque.", f_util_StringUtils::highlightString($string, $highlight));
		
		$highlight = "pharetra";
		$this->assertEquals("Pellentesque nisl urna, lacinia eget, sagittis nec, <start class=\"start\">pharetra</strong> eu, lectus? Proin pellentesque, ante at sodales egestas; pede est laoreet quam, nec a posuere lorem diam id neque.", f_util_StringUtils::highlightString($string, $highlight, "<start class=\"start\">"));
		$this->assertEquals("Pellentesque nisl urna, lacinia eget, sagittis nec, <start class=\"start\">pharetra</end> eu, lectus? Proin pellentesque, ante at sodales egestas; pede est laoreet quam, nec a posuere lorem diam id neque.", f_util_StringUtils::highlightString($string, $highlight, "<start class=\"start\">", "</end>"));
	}
	
	/**
	 * Tests f_util_StringUtils->strlen()
	 * @see f_util_StringUtils::strlen
	 */
	public function testStrlen()
	{
		$string = "string lenght";
		$this->assertEquals(13, f_util_StringUtils::strlen($string));
	}
	
	/**
	 * Tests f_util_StringUtils->substr()
	 * @see f_util_StringUtils::substr
	 */
	public function testSubstr()
	{
		$string = "string to be cut";
		$this->assertEquals("string t", f_util_StringUtils::substr($string, 0, 8));
		$this->assertEquals("be cut", f_util_StringUtils::substr($string, 10));
		$this->assertEquals("", f_util_StringUtils::substr($string, 20));
		$this->assertEquals("string to be cut", f_util_StringUtils::substr($string, 0, 30));
		$this->assertEquals("string to be cu", f_util_StringUtils::substr($string, 0, 15));
		$this->assertEquals("tring to be cut", f_util_StringUtils::substr($string, 1, 15));
	}
	
	/**
	 * Tests f_util_StringUtils->stripAccents()
	 * @see f_util_StringUtils::stripAccents
	 */
	public function testStripAccents()
	{
		$string = "àâäáãåÀÂÄÁÃÅæÆçÇèêëéÈÊËÉðÐìîïíÌÎÏÍñÑòôöóõøÒÔÖÓÕØœŒùûüúÙÛÜÚýÿÝŸ";
		$this->assertEquals("aaaaaaAAAAAAaeAEcCeeeeEEEEedEDiiiiIIIInNooooooOOOOOOoeOEuuuuUUUUyyYY", f_util_StringUtils::stripAccents($string));
	}
	
	/**
	 * Tests f_util_StringUtils->strtolower()
	 * @see f_util_StringUtils::strtolower
	 */
	public function testStrtolower()
	{
		$string = "StRiNg WiTh CaSe";
		$this->assertEquals("string with case", f_util_StringUtils::strtolower($string));
	}
	
	/**
	 * Tests f_util_StringUtils->strtoupper()
	 * @see f_util_StringUtils::strtoupper
	 */
	public function testStrtoupper()
	{
		$string = "StRiNg WiTh CaSe";
		$this->assertEquals("STRING WITH CASE", f_util_StringUtils::strtoupper($string));
	}
	
	/**
	 * Tests f_util_StringUtils->ucfirst()
	 * @see f_util_StringUtils::ucfirst
	 */
	public function testUcfirst()
	{
		$string = "ucfirst string";
		$this->assertEquals("Ucfirst string", f_util_StringUtils::ucfirst($string));
		
		$string = "ucfirst String";
		$this->assertEquals("Ucfirst String", f_util_StringUtils::ucfirst($string));
		
		$string = "Ucfirst String";
		$this->assertEquals("Ucfirst String", f_util_StringUtils::ucfirst($string));
	}
	
	/**
	 * Tests f_util_StringUtils->lcfirst()
	 * @see f_util_StringUtils::lcfirst
	 */
	public function testLcfirst()
	{
		$string = "lcfirst string";
		$this->assertEquals("lcfirst string", f_util_StringUtils::lcfirst($string));
		
		$string = "Lcfirst string";
		$this->assertEquals("lcfirst string", f_util_StringUtils::lcfirst($string));
		
		$string = "Lcfirst String";
		$this->assertEquals("lcfirst String", f_util_StringUtils::lcfirst($string));
	}
	
	/**
	 * Tests f_util_StringUtils->emailToAntispamString()
	 * @see f_util_StringUtils::emailToAntispamString
	 */
	public function testEmailToAntispamString()
	{
		$string = "john.doe@rbs.fr";
		$this->assertEquals("john.doe[at]rbs.fr", f_util_StringUtils::emailToAntispamString($string));
	}
	
	/**
	 * Tests f_util_StringUtils->quoteSingle()
	 * @see f_util_StringUtils::quoteSingle
	 */
	public function testQuoteSingle()
	{
		$string = "String with ' to be escape";
		$this->assertEquals("String with \\' to be escape", f_util_StringUtils::quoteSingle($string));
	}
	
	/**
	 * Tests f_util_StringUtils->quoteDouble()
	 * @see f_util_StringUtils::quoteDouble
	 */
	public function testQuoteDouble()
	{
		$string = 'String with " to be escape';
		$this->assertEquals('String with \" to be escape', f_util_StringUtils::quoteDouble($string));
	}
	
	/**
	 * Tests f_util_StringUtils->htmlToText()
	 * @see f_util_StringUtils::htmlToText
	 */
	public function testHtmlToText()
	{
		$string = '<div class="normal"><div class="bbcode default">Pellentesque nisl urna,' . K::CRLF . ' lacinia eget, sagittis nec, pharetra eu, lectus? <a href="http://www.rbschange.fr">Proin pellentesque</a>, ante at sodales egestas; pede est laoreet quam, a posuere lorem diam id neque.</div></div>';
		$this->assertEquals("Pellentesque nisl urna," . K::CRLF . " lacinia eget, sagittis nec, pharetra eu, lectus? Proin pellentesque [http://www.rbschange.fr], ante at sodales egestas; pede est laoreet quam, a posuere lorem diam id neque.", f_util_StringUtils::htmlToText($string));
		$this->assertEquals("Pellentesque nisl urna," . K::CRLF . " lacinia eget, sagittis nec, pharetra eu, lectus? Proin pellentesque, ante at sodales egestas; pede est laoreet quam, a posuere lorem diam id neque.", f_util_StringUtils::htmlToText($string, false));
		$this->assertEquals("Pellentesque nisl urna,  lacinia eget, sagittis nec, pharetra eu, lectus? Proin pellentesque, ante at sodales egestas; pede est laoreet quam, a posuere lorem diam id neque.", f_util_StringUtils::htmlToText($string, false, true));
	}
	
	/**
	 * Tests f_util_StringUtils->addCrLfToHtml()
	 * @see f_util_StringUtils::addCrLfToHtml
	 */
	public function testAddCrLfToHtml()
	{
		$string = '<div class="normal"><div class="bbcode default"><p>Pellentesque nisl urna,</p> lacinia eget, sagittis nec, pharetra eu, lectus? <p>Proin pellentesque, ante at sodales egestas; pede est laoreet quam, a posuere lorem diam id neque.</p></div></div>';
		$this->assertEquals('<div class="normal"><div class="bbcode default"><p>Pellentesque nisl urna,</p>
 lacinia eget, sagittis nec, pharetra eu, lectus? <p>Proin pellentesque, ante at sodales egestas; pede est laoreet quam, a posuere lorem diam id neque.</p>
</div>
</div>
', f_util_StringUtils::addCrLfToHtml($string));
	}
	
	/**
	 * Tests f_util_StringUtils->containsUppercasedLetter()
	 * @see f_util_StringUtils::containsUppercasedLetter
	 */
	public function testContainsUppercasedLetter()
	{
		$string = 'aaAjDyHsh9D82DséÉ';
		$this->assertTrue(f_util_StringUtils::containsUppercasedLetter($string));
		
		$string = 'aaAs';
		$this->assertTrue(f_util_StringUtils::containsUppercasedLetter($string));
		
		$string = 'aaB';
		$this->assertTrue(f_util_StringUtils::containsUppercasedLetter($string));
		
		$string = 'Baa';
		$this->assertTrue(f_util_StringUtils::containsUppercasedLetter($string));
		
		$string = 'B';
		$this->assertTrue(f_util_StringUtils::containsUppercasedLetter($string));
		
		$string = 'é';
		$this->assertFalse(f_util_StringUtils::containsUppercasedLetter($string));
		
		$string = 'É';
		$this->assertFalse(f_util_StringUtils::containsUppercasedLetter($string));
		
		$string = 'aaaa';
		$this->assertFalse(f_util_StringUtils::containsUppercasedLetter($string));
	}
	
	/**
	 * Tests f_util_StringUtils->containsLowercasedLetter()
	 * @see f_util_StringUtils::containsLowercasedLetter
	 */
	public function testContainsLowercasedLetter()
	{
		$string = 'aaAjDyHsh9D82Ds';
		$this->assertTrue(f_util_StringUtils::containsLowercasedLetter($string));
		
		$string = 'aaAs';
		$this->assertTrue(f_util_StringUtils::containsLowercasedLetter($string));
		
		$string = 'AAa';
		$this->assertTrue(f_util_StringUtils::containsLowercasedLetter($string));
		
		$string = 'aAA';
		$this->assertTrue(f_util_StringUtils::containsLowercasedLetter($string));
		
		$string = 'a';
		$this->assertTrue(f_util_StringUtils::containsLowercasedLetter($string));
		
		$string = 'é';
		$this->assertFalse(f_util_StringUtils::containsLowercasedLetter($string));
		
		$string = 'É';
		$this->assertFalse(f_util_StringUtils::containsLowercasedLetter($string));
		
		$string = 'AAAA';
		$this->assertFalse(f_util_StringUtils::containsLowercasedLetter($string));
	}
	
	/**
	 * Tests f_util_StringUtils->containsDigit()
	 * @see f_util_StringUtils::containsDigit
	 */
	public function testContainsDigit()
	{
		$string = 'aaAjDyHsh9D82Ds';
		$this->assertTrue(f_util_StringUtils::containsDigit($string));
		
		$string = 'aa9As';
		$this->assertTrue(f_util_StringUtils::containsDigit($string));
		
		$string = 'AA9';
		$this->assertTrue(f_util_StringUtils::containsDigit($string));
		
		$string = '9AA';
		$this->assertTrue(f_util_StringUtils::containsDigit($string));
		
		$string = '9';
		$this->assertTrue(f_util_StringUtils::containsDigit($string));
		
		$string = 'AA';
		$this->assertFalse(f_util_StringUtils::containsDigit($string));
	}
	
	/**
	 * Tests f_util_StringUtils->containsLetter()
	 * @see f_util_StringUtils::containsLetter
	 */
	public function testContainsLetter()
	{
		$string = 'aaAjDyHsh9D82Ds';
		$this->assertTrue(f_util_StringUtils::containsLetter($string));
		
		$string = 'AA9';
		$this->assertTrue(f_util_StringUtils::containsLetter($string));
		
		$string = 'aa9';
		$this->assertTrue(f_util_StringUtils::containsLetter($string));
		
		$string = '9BB';
		$this->assertTrue(f_util_StringUtils::containsLetter($string));
		
		$string = '9bb';
		$this->assertTrue(f_util_StringUtils::containsLetter($string));
		
		$string = 'b';
		$this->assertTrue(f_util_StringUtils::containsLetter($string));
		
		$string = 'A';
		$this->assertTrue(f_util_StringUtils::containsLetter($string));
		
		$string = 'é';
		$this->assertFalse(f_util_StringUtils::containsLetter($string));
		
		$string = 'É';
		$this->assertFalse(f_util_StringUtils::containsLetter($string));
		
		$string = '99';
		$this->assertFalse(f_util_StringUtils::containsLetter($string));
	}
	
	/**
	 * Tests f_util_StringUtils->isEmpty()
	 * @see f_util_StringUtils::isEmpty
	 */
	public function testIsEmpty()
	{
		$string = '';
		$this->assertTrue(f_util_StringUtils::isEmpty($string));
		
		$string = null;
		$this->assertTrue(f_util_StringUtils::isEmpty($string));
		
		$string = 0;
		$this->assertFalse(f_util_StringUtils::isEmpty($string));
		
		$string = '0';
		$this->assertFalse(f_util_StringUtils::isEmpty($string));
		
		$string = 'null';
		$this->assertFalse(f_util_StringUtils::isEmpty($string));
		
		$string = 'anull';
		$this->assertFalse(f_util_StringUtils::isEmpty($string));
	}
	
	/**
	 * Tests f_util_StringUtils->isNotEmpty()
	 * @see f_util_StringUtils::isNotEmpty
	 */
	public function testIsNotEmpty()
	{
		$string = '';
		$this->assertFalse(f_util_StringUtils::isNotEmpty($string));
		
		$string = null;
		$this->assertFalse(f_util_StringUtils::isNotEmpty($string));
		
		$string = 0;
		$this->assertTrue(f_util_StringUtils::isNotEmpty($string));
		
		$string = '0';
		$this->assertTrue(f_util_StringUtils::isNotEmpty($string));
		
		$string = 'null';
		$this->assertTrue(f_util_StringUtils::isNotEmpty($string));
		
		$string = 'anull';
		$this->assertTrue(f_util_StringUtils::isNotEmpty($string));
	}
	
	/**
	 * Tests f_util_StringUtils->JSONEncode()
	 * @see f_util_StringUtils::JSONEncode
	 */
	public function testJSONEncode()
	{
		$array = array('string', 'key1' => 'string1', 9, array('subarray1', 'subarray1.2', array('subarray2')));
		$this->assertEquals('{"0":"string","key1":"string1","1":9,"2":["subarray1","subarray1.2",["subarray2"]]}', f_util_StringUtils::JSONEncode($array));
	}
	
	/**
	 * Tests f_util_StringUtils->JSONDecode()
	 * @see f_util_StringUtils::JSONDecode
	 */
	public function testJSONDecode()
	{
		$array = array('string', 'key1' => 'string1', 9, array('subarray1', 'subarray1.2', array('subarray2')));
		$string = '{"0":"string","key1":"string1","1":9,"2":["subarray1","subarray1.2",["subarray2"]]}';
		$this->assertEquals($array, f_util_StringUtils::JSONDecode($string));
	}
	
	/**
	 * Tests f_util_StringUtils->utf8Decode()
	 * @see f_util_StringUtils::utf8Decode
	 */
	public function testUtf8Decode()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->lowerCaseFirstLetter()
	 * @see f_util_StringUtils::lowerCaseFirstLetter
	 */
	public function testLowerCaseFirstLetter()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->getFileExtension()
	 * @see f_util_StringUtils::getFileExtension
	 */
	public function testGetFileExtension()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->parray()
	 * @see f_util_StringUtils::parray
	 */
	public function testParray()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->var_dump_desc()
	 * @see f_util_StringUtils::var_dump_desc
	 */
	public function testVar_dump_desc()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->parseHtml()
	 * @see f_util_StringUtils::parseHtml
	 */
	public function testParseHtml()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->prefixUpper()
	 * @see f_util_StringUtils::prefixUpper
	 */
	public function testPrefixUpper()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->return_bytes()
	 * @see f_util_StringUtils::return_bytes
	 */
	public function testReturn_bytes()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->array_to_string()
	 * @see f_util_StringUtils::array_to_string
	 */
	public function testArray_to_string()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->stripnl()
	 * @see f_util_StringUtils::stripnl
	 */
	public function testStripnl()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->jsquote()
	 * @see f_util_StringUtils::jsquote
	 */
	public function testJsquote()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->php_to_js()
	 * @see f_util_StringUtils::php_to_js
	 */
	public function testPhp_to_js()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->mergeAttributes()
	 * @see f_util_StringUtils::mergeAttributes
	 */
	public function testMergeAttributes()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->cleanString()
	 * @see f_util_StringUtils::cleanString
	 */
	public function testCleanString()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->ordString()
	 * @see f_util_StringUtils::ordString
	 */
	public function testOrdString()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->prefixReplace()
	 * @see f_util_StringUtils::prefixReplace
	 */
	public function testPrefixReplace()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->containsCharBetween()
	 * @see f_util_StringUtils::containsCharBetween
	 */
	public function testContainsCharBetween()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->parseTextContent()
	 * @see f_util_StringUtils::parseTextContent
	 */
	public function testParseTextContent()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->handleAccent()
	 * @see f_util_StringUtils::handleAccent
	 */
	public function testHandleAccent()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->utf8Ereg()
	 * @see f_util_StringUtils::utf8Ereg
	 */
	public function testUtf8Ereg()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->utf8EregReplace()
	 * @see f_util_StringUtils::utf8EregReplace
	 */
	public function testUtf8EregReplace()
	{
		$this->markTestSkipped("Deprecated");
	}
	
	/**
	 * Tests f_util_StringUtils->utf8Eregi()
	 * @see f_util_StringUtils::utf8Eregi
	 */
	public function testUtf8Eregi()
	{
		$this->markTestSkipped("Deprecated");
	}
}