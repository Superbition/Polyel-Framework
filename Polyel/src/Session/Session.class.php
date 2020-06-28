<?php

namespace Polyel\Session;

use Polyel;
use Polyel\Http\Request;
use Polyel\Http\Response;

class Session
{
    // Holds the session manager service
    private $sessionManager;

    private $request;

    private $response;

    private $sessionID;

    // Used when a push call is made to indicate data should be pushed onto the session
    private $pushFlag = false;

    public function __construct(Request $request, Response $response)
    {
        $this->sessionManager = Polyel::call(SessionManager::class);
        $this->request = $request;
        $this->response = $response;
    }

    public function setID($sessionID)
    {
        $this->sessionID = $sessionID;
    }

    public function id()
    {
        return $this->sessionID;
    }

    /*
     * Returns all the session data
     */
    public function all()
    {
        $sessionID = $this->id();

        return $this->sessionManager->driver()->getSessionData($sessionID);
    }

    /*
     * Only returns the data part of the session
     */
    public function data()
    {
        $sessionData = $this->all();

        if(exists($sessionData) && array_key_exists('data', $sessionData))
        {
            return $sessionData['data'];
        }

        return null;
    }

    /*
     * Returns a specific key from the session data
     * Accepts a default value which is passed back if no match is found
     */
    public function get($key, $default = null)
    {
        // Split up the dot syntax request
        $sessionGetRequest = explode(".", $key);

        // Loop through the data until we find a matching key
        $sessionData = $this->data();
        foreach ($sessionGetRequest as $sessionGet)
        {
            // Loop through until we get a final value based on the dot syntax
            if(is_array($sessionData) && array_key_exists($sessionGet, $sessionData))
            {
                // Save the match to return...
                $sessionData = $sessionData[$sessionGet];
            }
            else
            {
                // No match could be found, return...
                return $default;
            }
        }

        // Return the requested data from the session
        return $sessionData;
    }

    /*
     * Used to check if the session has a certain key that is not null and is not empty.
     */
    public function has($key)
    {
        if(exists($this->get($key)))
        {
            return true;
        }

        return false;
    }

    /*
     * Used to check if a key exists but counts as existing if null or empty
     */
    public function exists($key)
    {
        $sessionData = $this->get($key, false);

        if(exists($sessionData) || is_null($sessionData))
        {
            return true;
        }

        return false;
    }

    /*
     * Used to add data to the session based on a given key and value.
     * Accepts a multi level key and will create the array levels if they do not exist.
     * Values are overwritten if the same key is passed again. Push should be used to add data.
     */
    public function store($keys, $value)
    {
        // Split up the dot syntax request
        $sessionStoreKeys = explode(".", $keys);

        // Grab all the session data so we can store new data
        $sessionData = $this->all();

        // Create a temp variable used to build up any array elements
        $sessionDataToStore = &$sessionData['data'];

        // Loop through the number of keys to get to the position of where the value should be placed
        foreach($sessionStoreKeys as $key)
        {
            // Create a new array when the key does not exist or is not already an array
            if(!isset($sessionDataToStore[$key]) || !is_array($sessionDataToStore[$key]))
            {
                // New array level because the key does not exist yet
                $sessionDataToStore[$key] = [];
            }

            // Using the temp level, keep increasing the array level so we can store the value
            $sessionDataToStore = &$sessionDataToStore[$key];
        }

        if($this->pushFlag === true)
        {
            // Finally, because of pass by ref, we can store the value but also PUSH onto the array
            $sessionDataToStore[] = $value;

            // Reset the push flag because we have already pushed our data to the end of the array
            $this->pushFlag = false;
        }
        else
        {
            // Finally, because of pass by ref, we can store the value, overwriting any previous value as well
            $sessionDataToStore = $value;
        }

        // We need the session ID to access the session data
        $sessionID = $this->id();

        // Using the driver from the session manager, re-save the data...
        $this->sessionManager->driver()->saveSessionData($sessionID, $sessionData);
    }

    /*
     * Used to add data to an array, adds data to the end of the session data array.
     * Will create array levels if they don't exist on the first call.
     */
    public function push($keys, $value)
    {
        /// Indicate that the data should be pushed onto the data array.
        $this->pushFlag = true;

        // Call store with the push flag already set
        $this->store($keys, $value);
    }

    /*
     * Pulls out data based on a given key but removes the key and its data from the session.
     * Returns a default value if the given key does not exist. Accepts dot syntax.
     */
    public function pull($keys, $default = null)
    {
        // The data we want to pull from the session
        $dataToReturn = $this->get($keys, $default);

        // Split up the dot syntax request
        $keys = explode(".", $keys);

        // Grab all the session data so we can store the new changes later...
        $sessionDataAll = $this->all();

        // Create a temp variable used to loop through and remove the data we have pulled out
        $sessionData = &$sessionDataAll['data'];

        $lastKey = array_pop($keys);

        foreach($keys as $key)
        {
            if(!isset($sessionData[$key]) || !is_array($sessionData[$key]))
            {
                // Given key(s) does not exist, return the default...
                return $default;
            }

            // Keep going along each given key
            $sessionData = &$sessionData[$key];
        }

        unset($sessionData[$lastKey]);

        // We need the session ID to access the session data
        $sessionID = $this->id();

        // Using the driver from the session manager, re-save the data...
        $this->sessionManager->driver()->saveSessionData($sessionID, $sessionDataAll);

        // The data was pulled out and removed, return the pulled data
        return $dataToReturn;
    }

    /*
     * Removes data from the session data.
     * Accepts a single key or an array of keys that can use dot syntax.
     */
    public function remove($keys)
    {
        // If the keys are an array, process them here...
        if(is_array($keys))
        {
            // Loop through each key and send one at a time to remove() recursively
            foreach($keys as $key)
            {
                $this->remove($key);
            }

            // After processing all array keys, return early as they would have been done in the for loop...
            return;
        }

        // Split up the dot syntax request
        $keys = explode(".", $keys);

        // Grab all the session data so we can store the new changes later...
        $sessionDataAll = $this->all();

        // Create a temp variable used to loop through and remove the data
        $sessionData = &$sessionDataAll['data'];

        $lastKey = array_pop($keys);

        foreach($keys as $key)
        {
            if(!isset($sessionData[$key]) || !is_array($sessionData[$key]))
            {
                // Given key is not found, return false...
                return false;
            }

            // Keep going along each given key until we get to the end
            $sessionData = &$sessionData[$key];
        }

        unset($sessionData[$lastKey]);

        // We need the session ID to access the session data
        $sessionID = $this->id();

        // Using the driver from the session manager, re-save the data...
        $this->sessionManager->driver()->saveSessionData($sessionID, $sessionDataAll);
    }

    /*
     * Clears the session data only, sets the data to null
     */
    public function clear()
    {
        // We need the session ID to access the session data
        $sessionID = $this->id();

        $this->sessionManager->driver()->clear($sessionID);
    }

    /*
     * Regenerates a session completely but passes the old data to the new session
     */
    public function regenerate()
    {
        // Get the current session ID and data
        $currentSessionID = $this->id();
        $oldSessionData = $this->all();

        if(exists($oldSessionData) && array_key_exists('id', $oldSessionData))
        {
            $this->sessionManager->driver()->destroySession($currentSessionID, false);

            $newSessionID = $this->sessionManager->regenerateSession($this->request, $this->response);

            $this->setID($newSessionID);

            $newSessionData = $this->all();

            $newSessionData['user_id'] = $oldSessionData['user_id'];
            $newSessionData['data'] = $oldSessionData['data'];

            $this->sessionManager->driver()->saveSessionData($newSessionID, $newSessionData);
        }
    }

    public function createCsrfToken()
    {
        if(!$this->exists('CSRF-TOKEN'))
        {
            $csrfToken = $this->sessionManager->generateCsrfToken();

            $this->store('CSRF-TOKEN', $csrfToken);
        }
    }
}