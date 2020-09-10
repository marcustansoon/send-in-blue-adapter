<?php
// Requires `GuzzleClient` library
require_once(__DIR__ . '/vendor/autoload.php');

class SendInBlueAdapter
{
	private static $API_KEY = '_YOUR_API_KEY_';
	private static $FOLDER_URL = 'https://api.sendinblue.com/v3/contacts/folders';
	private static $CONTACT_LIST_URL = 'https://api.sendinblue.com/v3/contacts/lists';
	private static $CONTACT_IMPORT_URL = 'https://api.sendinblue.com/v3/contacts/import';
	private static $CONTACT_FOLDER_URL = 'https://api.sendinblue.com/v3/contacts/folders';

	private $GuzzleClient;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    	$this->GuzzleClient = new GuzzleHttp\Client(['verify' => false, 'http_errors' => false]);
    }

    /**
     * Get all folders
     * 
     * @return array [{'id': int, 'name': string, 'totalBlacklisted': int, ...}, ...]
     */
    public function getFolders() : array
    {
    	$response = $this->GuzzleClient->request('GET', self::$FOLDER_URL, [
				'headers' => [
		        	'Accept' => 'application/json',
		        	'api-key' => [
		        		self::$API_KEY,
		        	],
		    	],
			],
		);

    	$responseData = json_decode($response->getBody()->getContents(), true);

    	return $response->getStatusCode() === 200 && json_last_error() === JSON_ERROR_NONE ? $responseData['folders'] ?? [] : [];
    }

    /**
     * Check if folder of specified name exists. If so, return its properties
     * 
     * @param  mixed $folderName 
     * @return ?array             
     */
    public function checkIfFolderExists($folderName_OR_ID) : ?array
    {
    	// Get folders
    	$folders = $this->getFolders();

    	// Check if folders exists
    	foreach($folders as $folder){
    		if(gettype($folderName_OR_ID) === 'string' && $folder['name'] === $folderName_OR_ID){
    			return $folder;
    		}else if(gettype($folderName_OR_ID) === 'integer' && $folder['id'] === $folderName_OR_ID){
    			return $folder;
    		}
    	}

    	return null;
    }

    /**
     * Create new folder
     * 
     * @param  string $folderName 
     * @return array     
     * ['id': int]        
     */
    public function createFolder(string $folderName) : array
    {
    	$response = $this->GuzzleClient->request('POST', self::$FOLDER_URL, [
			'headers' => [
		        'Accept' => 'application/json',
		        'api-key' => [
		        	self::$API_KEY,
		    	]
		    ],
		    'json' => [
		        'name' => $folderName,
		    ]
		]);

		return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Delete folder by id
     * 
     * @return ?array
     * On error:
     * 		['code': 'document_not_found', 'message': 'Folder ID does not exists']
     * On success:
     * 		null
     */
    public function deleteFolderById(int $folderId) : ?array
    {
    	$response = $this->GuzzleClient->request('DELETE', self::$FOLDER_URL.'/'.$folderId, [
			'headers' => [
		        'Accept' => 'application/json',
		        'api-key' => [
		        	self::$API_KEY,
		    	]
		    ],
		]);

		return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Delete Folder by name
     * 
     * @param  string $folderName [description]
     * @return [type]             [description]
     */
    public function deleteFolderByName(string $folderName) : ?array
    {
    	// Get folder by name
    	$folder = $this->checkIfFolderExists($folderName);

    	// Exit if folde does not exists
    	if(!$folder){
    		return null;
    	}

    	// Delete folder
    	return $this->deleteFolderById($folder['id']);
    }

    /**
     * 
     * 
     * @param  string $listName [description]
     * @param  int    $folderId [description]
     * @return array 
     * ['id': int]
     */
    public function createList(string $listName, int $folderId) : array
    {
		$response = $this->GuzzleClient->request('POST', self::$CONTACT_LIST_URL, [
			'headers' => [
		        'Accept' => 'application/json',
		        'api-key' => [
		        	self::$API_KEY,
		    	]
		    ],
		    'json' => [
		        'name' => $listName,
		        'folderId' => $folderId,
		    ]
		]);

		return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Create a list for specified folder name
     * 
     * @param  string $listName   [description]
     * @param  string $folderName [description]
     * @return ?array
     *
     * On error 
     * null
     * On success
     * ['id': ...]
     */
    public function createListByFolderName(string $listName, string $folderName) : ?array
    {
		// Get folder by name
    	$folder = $this->checkIfFolderExists($folderName);

    	// Exit if folde does not exists
    	if(!$folder){
    		return null;
    	}

    	// Delete folder
    	return $this->createList($listName, $folder['id']);
    }

    public function getListsInFolder($folderName_OR_ID)
    {
    	// Get folder by name OR id
    	$folder = $this->checkIfFolderExists($folderName_OR_ID);

    	// Exit if folde does not exists
    	if(!$folder){
    		return null;
    	}

    	$response = $this->GuzzleClient->request('GET', self::$CONTACT_FOLDER_URL."/".$folder['id']."/lists", [
			'headers' => [
		        'Accept' => 'application/json',
		        'api-key' => [
		        	self::$API_KEY,
		    	],
		    ],
		]);

    	$responseData = json_decode($response->getBody()->getContents(), true);

    	return $response->getStatusCode() === 200 && json_last_error() === JSON_ERROR_NONE ? $responseData['lists'] ?? [] : [];
    }

	public function writeToFile(string $data) : string
	{
		// Set filename and path
		$filePath = 'contact-lists/list-'.rand(1,50).'.txt';

		$file = fopen($filePath, "w") or die("Unable to open file!");
		fwrite($file, $data);
		fclose($file);

		// Return file path
		return $filePath;
	}

    public function getfileURL(string $filePath) : string
    {
    	// Get full url
		$fullURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$URLwithoutQueryParams = strtok($fullURL, '?');

		// Return file absolute URL
		return str_replace(basename(__FILE__), $filePath, $URLwithoutQueryParams);
    }

    public function importContacts(string $data, int $listId)
    {
    	// Create data
    	$filePath = $this->writeToFile($data);

    	// Get data URL
    	$fileURL = $this->getfileURL($filePath);
    	echo $fileURL;
    	
		$response = $this->GuzzleClient->request('POST', self::$CONTACT_IMPORT_URL, [
			'headers' => [
		        'Accept' => 'application/json',
		        'api-key' => [
		        	self::$API_KEY,
		    	]
		    ],
		    'json' => [
		        'fileUrl' => $fileURL,
		        'listIds' => [$listId],
		        'notifyUrl' => '',
		        'emailBlacklist' => false,
		        'smsBlacklist' => false,
		        'updateExistingContacts' => true,
		        'emptyContactsAttributes' => false,
		    ]
		]);

		return json_decode($response->getBody()->getContents(), true);
    }
}

$test = new SendInBlueAdapter();

//dd($test->getListsInFolder(50));
dd($test->importContacts("EMAIL,FIRSTNAME,LASTNAME,SMS,CREATION_DATE,GENDER,SURNAME\nsubscriber1@example.com,firstname1,lastname1,33611111111,2019-01-15,1,asd\nsubscriber2@example.com,firstname2,lastname2,33611111112,2019-03-10,2,bcd\nsubscriber3@example.com,firstname3,lastname3,33611111113,2019-08-22,1,lop", 53));

//53

/*
try {
    $result = $apiInstance->createContact($createContact);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ContactsApi->createContact: ', $e->getMessage(), PHP_EOL;
}*/
?>
