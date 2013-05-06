<?php
/**
 * Script: NaturalDocs
 * ___________________________________________________________________________
 *
 * Version 1.52
 *
 * Copyright © 2003-2010 Greg Valure
 *
 * http://www.naturaldocs.org
 *
 * Natural Docs is licensed under version 3 of the GNU Affero General Public License (AGPL).
 * Refer to the <License> for the complete details.
 *
 * Topic: Code Conventions
 *
 * -	Every package function is called with an arrow operator.  It's needed for inheritance in some
 * 		places, and consistency when it's not.
 *
 * -	No constant will ever be zero or undef.  Those are reserved so any piece of code can
 * 		allow a "none of the above" option and not worry about conflicts with an existing value.
 *
 * -	Existence hashes are hashes where the value doesn't matter.  It acts more as a set, where
 * 		the existence of the key is the significant part.
 *
 * Topic: File Format Conventions
 *
 * -	All integers appear in big-endian format.  So a UInt16 should be handled with a 'n' in
 * 		pack and unpack, not with a 'S'.
 *
 * -	UString16's are a big-endian UInt16 followed by that many UTF-8 bytes.  A null-terminator is not stored.
 *
 * -	If a higher-level type is described in a file format, that means the loading and saving
 * 		format is handled by that package.  For example, if you see <SymbolString> in the format,
 * 		that means <NaturalDocs::SymbolString->ToBinaryFile()> and <NaturalDocs::SymbolString->FromBinaryFile()> are
 * 		used to manipulate it, and the underlying format should be treated as opaque.
 */

/**
 *	Group: Basic Types
 *
 *	Types used throughout the program.  As Perl is a weakly-typed language unless you box things into
 *	objects, these types are for documentation purposes and are not enforced.
 *
 *	Type: FileName
 *
 *	A string representing the absolute, platform-dependent path to a file.  Relative file paths are no
 *	longer in use anywhere in the program.  All path manipulation should be done through <NaturalDocs::File>.
 *
 *	Type: VersionInt
 *
 *	A comparable integer representing a version number.  Converting them to and from text and binary
 *	should be handled by <NaturalDocs::Version>.
 *
 *	Type: SymbolString
 *
 *	A scalar which encodes a normalized array of identifier strings representing a full or
 *	partially-resolved symbol.  All symbols must be retrieved from plain text via <NaturalDocs::SymbolString->FromText()>
 *	so that the separation and normalization is always consistent.  SymbolStrings are comparable via string compare
 *	functions and are sortable.
 *
 *	Type: ReferenceString
 *
 *	All the information about a reference that makes it unique encoded into a string.  This includes
 *	the <SymbolString> of the reference, the scope <SymbolString> it appears in, the scope <SymbolStrings>
 *	it has access to via "using", and the <ReferenceType>.  This is done because if any of those parameters
 *	change, it needs to be treated as a completely separate reference.
 */

/**
 *	Group: Support Functions
 *	General functions that are used throughout the program, and that don't really fit anywhere else.
 */

/**
 *	Function: StringCompare
 *
 *	Compares two strings so that the result is good for proper sorting.  A proper sort orders the characters as
 *	follows:
 *		- End of string.
 *		- Whitespace.  Line break-tab-space.
 *		- Symbols, which is anything not included in the other entries.
 *		- Numbers, 0-9.
 *		- Letters, case insensitive except to break ties.
 *
 *	If you use cmp instead of this function, the result would go by ASCII/Unicode values which would place certain symbols
 *	between letters and numbers instead of having them all grouped together.  Also, you would have to choose between case
 *	sensitivity or complete case insensitivity, in which ties are broken arbitrarily.
 *
 *	Returns:
 *
 *	Like cmp, it returns zero if A and B are equal, a positive value if A is greater than B,
 *	and a negative value if A is less than B.
 */
function StringCompare(&$a,&$b)
{
	if (!$a){
		return !$b ? 0 : -1;
	}elseif(!$b){
		return 1;
	}

	//	FIXME: ? Will this cause multibyte strings to convert incorrectly?
	$translatedA = strtolower($a);
	$translatedB = strtolower($b);

	$translatedA = strtr($translatedA,"\n\r\t 0-9a-z","\x01\x02\x03\x04\xDB-\xFE");
	$translatedB = strtr($translatedB,"\n\r\t 0-9a-z","\x01\x02\x03\x04\xDB-\xFE");

	$result = strcasecmp($translatedA,$translatedB):

	if ($result == 0)
	{
		//	Break the tie by comparing their case.  Lowercase before uppercase.
		//	If statement just to keep everything theoretically kosher, even though in practice we don't need this.
		if (ord('A') > ord('a')){
			return strcasecmp($a,$b);
		}else{
			return strcasecmp($b,$a);
		}
	}else{
		return $result;
	}
}

/**
 * Function: ShortenToMatchStrings
 *
 * Compares two arrayrefs and shortens the first array to only contain shared entries.  Assumes all entries are strings.
 *
 * Parameters:
 * 	sharedArrayRef	- The arrayref that will be shortened to only contain common elements.
 * 	compareArrayRef	- The arrayref to match.
 */
function ShortenToMatchStrings(&$sharedArrayRef,$compareArrayRef)
{
	my $index = 0;

	while (	$index < count($sharedArrayRef)
			&& $index < count($compareArrayRef)
			&& $sharedArrayRef[$index] == $compareArrayRef[$index])
	{
		$index++;
	}

	if ($index < count($sharedArrayRef))
	{
		array_slice($sharedArrayRef,0,$index);
	}
}

/**
 * Function: FindFirstSymbol
 *
 * Searches a string for a number of symbols to see which appears first.
 *
 * Parameters:
 * 	string - The string to search.
 * 	symbols - An arrayref of symbols to look for.
 * 	index - The index to start at, if any.
 *
 * Returns:
 * 	The array ( index, symbol ).
 * 		index - The index the first symbol appears at, or -1 if none appear.
 * 		symbol - The symbol that appeared, or undef if none.
 */
function FindFirstSymbol($string,$symbols,$index)
{
	if (!isset($index)) $index = 0;

	$lowestIndex	=	-1;
	$lowestSymbol	=	NULL;

	foreach($symbols as $symbol)
	{
		$testIndex = stripos($string, $symbol, $index);

		if ($testIndex != -1 && ($lowestIndex == -1 || $testIndex < $lowestIndex))
		{
			$lowestIndex	=	$testIndex;
			$lowestSymbol	=	$symbol;
		};
	};

	return array($lowestIndex, $lowestSymbol);
};

class PHP_Natural_Docs
{
	public function __construct()
	{

	}

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	/**
	 *	Main Code
	 *
	 *	The order in which functions are called here is critically important.
	 *	Read the "Usage and Dependencies" sections of all the packages before
	 *	even thinking about rearranging these.
	 */
	public function execute()
	{
		//	Check that our required packages are okay.
		PHP_Natural_Docs_File::checkCompatibility();

		//	Almost everything requires Settings to be initialized.
		PHP_Natural_Docs_Settings::load();

		PHP_Natural_Docs_Project::loadConfigFileInfo();

		PHP_Natural_Docs_Topics::load();
		PHP_Natural_Docs_Languages::load();

		//	Migrate from the old file names that were used prior to 1.14.
		PHP_Natural_Docs_Project::migrateOldFiles();

		self::output("Finding files and detecting changes...\n"):

		PHP_Natural_Docs_Project::loadSourceFileInfo();
		PHP_Natural_Docs_Project::loadImageFileInfo();

		//	Register SourceDB extensions.  Order is important.
		PHP_Natural_Docs_Image_Reference_Table::register();

		PHP_Natural_Docs_Symbol_Table::load();
		PHP_Natural_Docs_Class_Hierarchy::load();
		PHP_Natural_Docs_Source_DB::load();

		PHP_Natural_Docs_Symbol_Table::purge();
		PHP_Natural_Docs_Class_Hierarchy::purge();
		PHP_Natural_Docs_Source_DB::purgeDeletedSourceFiles();

		//	Parse any supported files that have changed.
		$filesToParse = PHP_Natural_Docs_Project::filesToParse();

		foreach(Amslib_Array::valid($filesToParse) as $k=>$file){
			if($k==0){
				PHP_Natural_Docs_Status_Message::start("Parsing $amount file".($amount > 1 ? "s" : "")."...");
			}

			PHP_Natural_Docs_Parser::parseForInformation($file);
			PHP_Natural_Docs_Status_Message::completedItem();
		}

		//	The symbol table is now fully resolved, so we can reduce its memory footprint.
		PHP_Natural_Docs_Symbol_Table::purgeResolvingInfo();

		//	Load and update the menu file.  We need to do this after parsing so when it is updated,
		//	it will detect files where the default menu title has changed and files that have added
		//	or deleted Natural Docs content.
		PHP_Natural_Docs_Menu::loadAndUpdate();

		//	Build any files that need it. This needs to be run regardless of whether there are
		//	any files to build.  It will handle its own output messages.
		PHP_Natural_Docs_Builder::run();

		// Write the changes back to disk.
		PHP_Natural_Docs_Menu::save();
		PHP_Natural_Docs_Project::saveImageFileInfo();
		PHP_Natural_Docs_Project::saveSourceFileInfo();
		PHP_Natural_Docs_Symbol_Table::save();
		PHP_Natural_Docs_Class_Hierarchy::save();
		PHP_Natural_Docs_Source_DB::save();
		PHP_Natural_Docs_Settings::save();
		PHP_Natural_Docs_Topics::save();
		PHP_Natural_Docs_Languages::save();

		# Must be done last.
		PHP_Natural_Docs_Project::saveConfigFileInfo();

		self::output("Done.\n");

		/*if ($EVAL_ERROR)  # Oops.
		{
			NaturalDocs::Error->HandleDeath();
		};*/
	}

	static public function output($string)
	{
		if(!PHP_Natural_Docs_Settings::isQuiet())
		{
			print($string);
		}
	}
}