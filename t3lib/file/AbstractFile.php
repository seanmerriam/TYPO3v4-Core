<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Ingmar Schlecht <ingmar@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Abstract file representation in the file abstraction layer.
 *
 * @author Ingmar Schlecht <ingmar@typo3.org>
 * @package  TYPO3
 * @subpackage  t3lib
 */
abstract class t3lib_file_AbstractFile implements t3lib_file_FileInterface {

	/**
	 * Various file properties
	 *
	 * Note that all properties, which only the persisted (indexed) files have are stored in this
	 * overall properties array only. The only properties which really exist as object properties of
	 * the file object are the storage, the identifier, the fileName and the indexing status.
	 *
	 * @var array
	 */
	protected $properties;

	/**
	 * The storage this file is located in
	 *
	 * @var t3lib_file_Storage
	 */
	protected $storage = NULL;

	/**
	 * The identifier of this file to identify it on the storage.
	 * On some drivers, this is the path to the file, but drivers could also just
	 * provide any other unique identifier for this file on the specific storage.
	 *
	 * @var string
	 */
	protected $identifier;

	/**
	 * The file name of this file
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * If set to true, this file is regarded as being deleted.
	 *
	 * @var boolean
	 */
	protected $deleted = FALSE;


	/**
	 * any other file
	 */
	const FILETYPE_UNKNOWN = 0;

	/**
	 * Any kind of text
	 */
	const FILETYPE_TEXT = 1;

	/**
	 * Any kind of image
	 */
	const FILETYPE_IMAGE = 2;

	/**
	 * Any kind of audio file
	 */
	const FILETYPE_AUDIO = 3;

	/**
	 * Any kind of video
	 */
	const FILETYPE_VIDEO = 4;

	/**
	 * Any kind of software, often known as "application"
	 */
	const FILETYPE_SOFTWARE = 5;

	/*******************************
	 * VARIOUS FILE PROPERTY GETTERS
	 *******************************

	/**
	 * Returns true if the given property key exists for this file.
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function hasProperty($key) {
		return isset($this->properties[$key]);
	}

	/**
	 * Returns a property value
	 *
	 * @param string $key
	 * @return mixed Property value
	 */
	public function getProperty($key) {
		return $this->properties[$key];
	}

	/**
	 * Returns the properties of this object.
	 *
	 * @return array
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * Returns the identifier of this file
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}


	/**
	 * Returns the name of this file
	 *
	 * @return string
	 */
	public function getName() {
			// Do not check if file has been deleted because we might need the
			// name for undeleting it.
		return $this->name;
	}


	/**
	 * Returns the size of this file
	 *
	 * @return integer
	 */
	public function getSize() {
		if ($this->deleted) {
			throw new RuntimeException('File has been deleted.', 1329821480);
		}

		return $this->properties['size'];
	}

	/**
	 * Returns the uid of this file
	 *
	 * @return integer
	 */
	public function getUid() {
		return $this->getProperty('uid');
	}

	/**
	 * Returns the Sha1 of this file
	 *
	 * @return string
	 */
	public function getSha1() {
		if ($this->deleted) {
			throw new RuntimeException('File has been deleted.', 1329821481);
		}

		return $this->getStorage()->hashFile($this, 'sha1');
	}

	/**
	 * Returns the creation time of the file as Unix timestamp
	 *
	 * @return integer
	 */
	public function getCreationTime() {
		if ($this->deleted) {
			throw new RuntimeException('File has been deleted.', 1329821487);
		}

		return $this->getProperty('creation_date');
	}

	/**
	 * Returns the date (as UNIX timestamp) the file was last modified.
	 *
	 * @return integer
	 */
	public function getModificationTime() {
		if ($this->deleted) {
			throw new RuntimeException('File has been deleted.', 1329821488);
		}

		return $this->getProperty('modification_date');
	}

	/**
	 * Get the extension of this file in a lower-case variant
	 *
	 * @return string The file extension
	 */
	public function getExtension() {
		return strtolower(pathinfo($this->getName(), PATHINFO_EXTENSION));
	}

	/**
	 * Get the MIME type of this file
	 *
	 * @return array file information
	 */
	public function getMimeType() {
			// TODO this will be slow - use the cached version if possible
		$stat = $this->getStorage()->getFileInfo($this);
		return $stat['mimetype'];
	}

	/**
	 * Returns the fileType of this file
	 * basically there are only five main "file types"
	 * "audio"
	 * "image"
	 * "software"
	 * "text"
	 * "video"
	 * "other"
	 * see the constants in this class
	 *
	 * @return integer $fileType
	 */
	public function getType() {
			// this basically extracts the mimetype and guess the filetype based
			// on the first part of the mimetype works for 99% of all cases, and
			// we don't need to make an SQL statement like EXT:media does currently
		if (!$this->properties['type']) {
			$mimeType = $this->getMimeType();
			list($fileType) = explode('/', $mimeType);

			switch (strtolower($fileType)) {
				case 'text':
					$this->properties['type'] = self::FILETYPE_TEXT;
					break;
				case 'image':
					$this->properties['type'] = self::FILETYPE_IMAGE;
					break;
				case 'audio':
					$this->properties['type'] = self::FILETYPE_AUDIO;
					break;
				case 'video':
					$this->properties['type'] = self::FILETYPE_VIDEO;
					break;
				case 'application':
				case 'software':
					$this->properties['type'] = self::FILETYPE_SOFTWARE;
					break;
				default:
					$this->properties['type'] = self::FILETYPE_UNKNOWN;
			}
		}

		return $this->properties['type'];
	}


	/******************
	 * CONTENTS RELATED
	 ******************/

	/**
	 * Get the contents of this file
	 *
	 * @return string File contents
	 */
	public function getContents() {
		if ($this->deleted) {
			throw new RuntimeException('File has been deleted.', 1329821479);
		}

		return $this->getStorage()->getFileContents($this);
	}

	/**
	 * Replace the current file contents with the given string
	 *
	 * @param string $contents The contents to write to the file.
	 * @return t3lib_file_File The file object (allows chaining).
	 */
	public function setContents($contents) {
		if ($this->deleted) {
			throw new RuntimeException('File has been deleted.', 1329821478);
		}

		$this->getStorage()->setFileContents($this, $contents);
		return $this;
	}

	/****************************************
	 * STORAGE AND MANAGEMENT RELATED METHDOS
	 ****************************************/

	/**
	 * Get the storage this file is located in
	 *
	 * @return t3lib_file_Storage
	 */
	public function getStorage() {
		if ($this->storage === NULL) {
			$this->loadStorage();
		}

		return $this->storage;
	}

	/**
	 * Loads the storage object of this file object.
	 *
	 * @return void
	 */
	protected function loadStorage() {
		$storageUid = $this->getProperty('storage');
		if (t3lib_utility_Math::canBeInterpretedAsInteger($storageUid)) {

			/** @var $fileFactory t3lib_file_Factory */
			$fileFactory = t3lib_div::makeInstance('t3lib_file_Factory');
			$this->storage = $fileFactory->getStorageObject($storageUid);
		}
	}

	/**
	 * Checks if this file exists. This should normally always return TRUE;
	 * it might only return FALSE when this object has been created from an
	 * index record without checking for.
	 *
	 * @return boolean TRUE if this file physically exists
	 */
	public function exists() {
		if ($this->deleted) {
			return FALSE;
		}

		return $this->storage->hasFile($this->getIdentifier());
	}


	/**
	 * Sets the storage this file is located in. This is only meant for
	 * t3lib/file/-internal usage; don't use it to move files.
	 *
	 * @internal Should only be used by other parts of the File API (e.g. drivers after moving a file)
	 * @param integer|t3lib_file_Storage $storage
	 * @return t3lib_file_File
	 */
	public function setStorage($storage) {
			// Do not check for deleted file here as we might need this method for the recycler later on
		if (is_object($storage) && $storage instanceof t3lib_file_Storage) {
			$this->storage = $storage;
			$this->properties['storage'] = $storage->getUid();
		} else {
			$this->properties['storage'] = $storage;
			$this->storage = NULL;
		}
		return $this;
	}

	/**
	 * Set the identifier of this file
	 *
	 * @internal Should only be used by other parts of the File API (e.g. drivers after moving a file)
	 * @param string $identifier
	 * @return string
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * Returns a combined identifier of this file, i.e. the storage UID and the
	 * folder identifier separated by a colon ":".
	 *
	 * @return string Combined storage and file identifier, e.g. StorageUID:path/and/fileName.png
	 */
	public function getCombinedIdentifier() {
		if (is_array($this->properties) && t3lib_utility_Math::canBeInterpretedAsInteger($this->properties['storage'])) {
			$combinedIdentifier = $this->properties['storage'] . ':' . $this->getIdentifier();
		} else {
			$combinedIdentifier = $this->getStorage()->getUid() . ':' . $this->getIdentifier();
		}

		return $combinedIdentifier;
	}

	/**
	 * Deletes this file from its storage. This also means that this object becomes useless.
	 *
	 * @return bool TRUE if deletion succeeded
	 * TODO mark file internally as deleted, throw exceptions on all method calls afterwards
	 * TODO undelete mechanism?
	 */
	public function delete() {
			// The storage will mark this file as deleted
		return $this->getStorage()->deleteFile($this);
	}

	/**
	 * Marks this file as deleted. This should only be used inside the
	 * File Abstraction Layer, as it is a low-level API method.
	 *
	 * @return void
	 */
	public function setDeleted() {
		$this->deleted = TRUE;
	}

	/**
	 * Returns TRUE if this file has been deleted
	 *
	 * @return boolean
	 */
	public function isDeleted() {
		return $this->deleted;
	}

	/**
	 * Renames this file.
	 *
	 * @param string $newName The new file name
	 * @return t3lib_file_File
	 */
	public function rename($newName) {
		if ($this->deleted) {
			throw new RuntimeException('File has been deleted.', 1329821482);
		}

		return $this->getStorage()->renameFile($this, $newName);
	}

	/**
	 * Copies this file into a target folder
	 *
	 * @param t3lib_file_Folder $targetFolder Folder to copy file into.
	 * @param string $targetFileName an optional destination fileName
	 * @param string $conflictMode overrideExistingFile", "renameNewFile", "cancel"
	 *
	 * @return t3lib_file_File The new (copied) file.
	 */
	public function copyTo(t3lib_file_Folder $targetFolder, $targetFileName = NULL, $conflictMode = 'renameNewFile') {
		if ($this->deleted) {
			throw new RuntimeException('File has been deleted.', 1329821483);
		}

		return $targetFolder->getStorage()->copyFile($this, $targetFolder, $targetFileName, $conflictMode);
	}

	/**
	 * Moves the file into the target folder
	 *
	 * @param t3lib_file_Folder $targetFolder Folder to move file into.
	 * @param string $targetFileName an optional destination fileName
	 * @param string $conflictMode overrideExistingFile", "renameNewFile", "cancel"
	 *
	 * @return t3lib_file_File This file object, with updated properties.
	 */
	public function moveTo(t3lib_file_Folder $targetFolder, $targetFileName = NULL, $conflictMode = 'renameNewFile') {
		if ($this->deleted) {
			throw new RuntimeException('File has been deleted.', 1329821484);
		}

		return $targetFolder->getStorage()->moveFile($this, $targetFolder, $targetFileName, $conflictMode);
	}


	/*****************
	 * SPECIAL METHODS
	 *****************/

	/**
	 * Returns a publicly accessible URL for this file
	 *
	 * WARNING: Access to the file may be restricted by further means, e.g. some
	 * web-based authentication. You have to take care of this yourself.
	 *
	 * @param bool  $relativeToCurrentScript   Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
	 *
	 * @return string
	 */
	public function getPublicUrl($relativeToCurrentScript = FALSE) {
		if ($this->deleted) {
			throw new RuntimeException('File has been deleted.', 1329821485);
		}

		return $this->getStorage()->getPublicUrl($this, $relativeToCurrentScript);
	}

	/**
	 * Returns a path to a local version of this file to process it locally (e.g. with some system tool).
	 * If the file is normally located on a remote storages, this creates a local copy.
	 * If the file is already on the local system, this only makes a new copy if $writable is set to TRUE.
	 *
	 * @param boolean $writable Set this to FALSE if you only want to do read operations on the file.
	 * @return string
	 */
	public function getForLocalProcessing($writable = TRUE) {
		if ($this->deleted) {
			throw new RuntimeException('File has been deleted.', 1329821486);
		}

		return $this->getStorage()->getFileForLocalProcessing($this, $writable);
	}


	/***********************
	 * INDEX RELATED METHODS
	 ***********************/

	/**
	 * Updates properties of this object.
	 * This method is used to reconstitute settings from the
	 * database into this object after being intantiated.
	 *
	 * @param array $properties
	 */
	abstract public function updateProperties(array $properties);
}

?>