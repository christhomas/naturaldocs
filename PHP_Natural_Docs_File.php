<?php
/******************************************************************************
 *	Package: NaturalDocs::File
 ******************************************************************************
 *
 *	A package to manage file access across platforms.  Incorporates functions
 *	from various standard File:: packages, but more importantly, works around
 *	the glorious suckage present in File::Spec, at least in version 0.82 and
 *	earlier.  Read the "Why oh why?" sections for why this package was necessary.
 *
 *	Usage and Dependencies:
 *		-	The package doesn't depend on any other Natural Docs packages and is ready to use immediately.
 *		-	All functions except <CanonizePath()> assume that all parameters are canonized.
 *
 ******************************************************************************
 *
 *	This file is part of Natural Docs, which is Copyright © 2003-2010 Greg Valure
 *	Natural Docs is licensed under version 3 of the GNU Affero General Public License (AGPL)
 *	Refer to License.txt for the complete details
 */
class PHP_Natural_Docs_File
{
	public function __construct()
	{

	}

	/**
	 *	Function: CheckCompatibility
	 *
	 *	Checks if the standard packages required by this one are up to snuff and
	 *	dies if they aren't.  This is done because I can't tell which versions of
	 *	File::Spec have splitpath just by the version numbers.
	 */
	static public function checkCompatibility()
	{
		/** CONVERT_PERL_PHP >>>
		 *	NOTE: I think I can ignore this function, since I'm converting to PHP, it might not be necessary

		my ($self) = @_;

		eval {
			File::Spec->splitpath('');
		};

		if ($@)
		{
			NaturalDocs::Error->SoftDeath("Natural Docs requires a newer version of File::Spec than you have.  You must either upgrade it or upgrade Perl.");
		};
		<<< */
	}

	/**	EXISTING FUNCTIONS NOT IMPLEMENTED YET
	 *	sub CanonizePath #(path)
	 *	sub PathIsAbsolute #(path)
	 *	sub JoinPath #(volume, dirString, $file)
	 *	sub JoinPaths #(basePath, extraPath, noFileInExtra)
	 *	sub SplitPath #(path, noFile)
	 *	sub MakeRelativePath #(basePath, targetPath)
	 *	sub IsSubPathOf #(base, path)
	 *	sub ConvertToURL #(path)
	 *	sub NoUpwards #(array)
	 *	sub NoFileName #(path)
	 *	sub NoExtension #(path)
	 *	sub ExtensionOf #(path)
	 *	sub IsCaseSensitive
	 *	sub CreatePath #(path)
	 *	sub RemoveEmptyTree #(path, limit)
	 *	sub Copy #(source, destination) => bool
	*/
}