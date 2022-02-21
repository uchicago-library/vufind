<?php
namespace VuFindAdmin\Controller;
use VuFind\Db\Row\User;

class PinController extends AbstractAdmin
{
    /**
     * Params
     *
     * @var array
     */
    protected $params;

    /**
     * Db
     */
    protected $db;

    protected function setupDatabase() {
        $config = $this->getConfig();

	    // pull settings out of database config
	    $database_info = str_replace('mysql://', '', $config->Database->database);
        $database_info_array = explode('@', $database_info);
	    list($user, $password) = explode(':', array_shift($database_info_array));
	    list($host, $dbname) = explode('/', array_pop($database_info_array));

        // log into the database and just display some stuff. 
        $this->db = new \PDO(
            sprintf("mysql:host=%s;port=3306;dbname=%s", $host, $dbname),
            $user,
            $password
        );

        $this->db->query(sprintf("USE %s;", $dbname));
    }

    /**
     * Get the url parameters
     *
     * @param string $param A key to check the url params for
     *
     * @return string
     */

    protected function getParam($param)
    {
        return (isset($this->params[$param]))
            ? $this->params[$param]
            : $this->params()->fromPost(
                $param,
                $this->params()->fromQuery($param, null)
            );
    }

    /**
     * "Create account" action
     *
     * @return mixed
     *
     * Copied from MyResearchController. Moved into this controller so
     * we can lock it down to a whitelist of users. -jej
     */
    public function createAction()
    {
        $this->setupDatabase();

        $view = $this->createViewModel();
        $view->setTemplate('admin/pin/create');

        $messages = array();

        $form_barcode = '';
        $form_lastname = '';

        // Process request, if necessary:
        if (!is_null($this->params()->fromPost('submit', null))) {
            $form_barcode = $this->params()->fromPost('barcode');
            $form_lastname = $this->params()->fromPost('lastname');
            $form_pin1 = $this->params()->fromPost('pin1');
            $form_pin2 = $this->params()->fromPost('pin2');

            if ($form_barcode == '') {
                $messages[] = "Barcode is required.";
            }
            if ($form_lastname == '') {
                $messages[] = "Last name is required.";
            }
            if ($form_pin1 == '') {
                $messages[] = "PIN is required.";
            }
            if ($form_pin1 != $form_pin2) {
                $messages[] = "PINs must match.";
            }

	        $sql = 'SELECT * FROM user WHERE username = :barcode AND lastname = :lastname';
	        $sth = $this->db->prepare($sql);
	        $sth->execute(array(
	            ':barcode' => $form_barcode,
	            ':lastname' => $form_lastname
	        ));
	        $results = $sth->fetchAll();

            // Account already exists.
            if (count($results) > 0) {
                $messages[] = "This account already exists in VuFind.";
            }
       
            // Try to insert a new account into VuFind 
            if (count($messages) == 0) {
                // get profile info from the backend. 
                $catalog = $this->getILS();
                $patron = $catalog->patronLogin($form_barcode, $form_pin1);
                $profile = $catalog->getMyProfile($patron);
                if (!array_key_exists('firstname', $profile) || 
                    !array_key_exists('lastname', $profile) ||
                    !array_key_exists('email', $patron)) {
                    $messages[] = "Can't retrieve account information from OLE.";
                } else {
                    $firstname = $profile['firstname'];
                    $lastname = $profile['lastname'];
                    $email = $patron['email'];

                    if (strtolower($lastname) != strtolower($form_lastname)) {
                        $messages[] = "No information available for that barcode/last name combination.";
                    } else {
                        //$sql = "INSERT INTO user VALUES (NULL, :barcode, :pin, NULL, :firstname, :lastname, :email, :barcode, :catpassword, NULL,'','','', :date, '', NULL, :date, NULL)";
                        $sql = "INSERT INTO `user` (username, password, firstname, lastname, email, cat_username, cat_password, created) VALUES (:barcode, :pin, :firstname, :lastname, :email, :barcode, :catpassword, :date)";
				        $sth = $this->db->prepare($sql);
				        $r = $sth->execute(array(
	                        ':barcode' => $form_barcode,
	                        ':pin' => $form_pin1,
	                        ':firstname' => $firstname,
	                        ':lastname' => $lastname,
	                        ':email' => $email,
	                        ':catpassword' => strtolower($lastname),
	                        ':date' => date('Y-m-d H:i:s')
	                    ));

                        $affected_rows = $sth->rowCount();

                        if ($r == false || $affected_rows != 1) {
                            $messages[] = "There was a problem adding this record. Please contact support.";
                        } else {
                            $this->flashMessenger()->setNamespace('info')->addMessage(sprintf("%s has been added to the VuFind database.", $form_barcode));
                            return $this->forwardTo('Pin', 'Home');
                        }
                    }
                }
            }
        }

        if (!empty($messages)) {
            $this->flashMessenger()->setNamespace('error')->addMessage(implode(' ', $messages));
        }

        // Pass request to view so we can repopulate user parameters in form:
        $view->barcode = $form_barcode;
        $view->lastname = $form_lastname;
        return $view;
    }

    /**
     * "Delete account" action
     */
    public function deleteAction()
    {
        $this->setupDatabase();

        $barcode = $this->params()->fromQuery('barcode');

	    $view = $this->createViewModel();
	    $view->setTemplate('admin/pin/delete');
	    $view->barcode = $barcode;
        $view->firstname = '';
        $view->lastname = '';

        // Try to delete the account from VuFind. 
        if (!is_null($this->params()->fromPost('submit', null))) {
	        $sql = 'DELETE FROM user WHERE username = :username';
	        $sth = $this->db->prepare($sql);
	        $r = $sth->execute(array(
	            ':username' => $barcode
	        ));
            $affected_rows = $sth->rowCount();

            if ($r && $affected_rows == 1) {
                $this->flashMessenger()->setNamespace('info')->addMessage(sprintf("%s has been deleted from the VuFind database.", $barcode));
                return $this->forwardTo('Pin', 'Home');
            } else {
                $this->flashMessenger()->setNamespace('error')->addMessage("There was a problem deleting this record. Please contact support.");
                return $view; 
            }

            
        // Display the barcode and name for the account that's about to
        // be deleted.
        } else {
	        $sql = 'SELECT * FROM user WHERE username = :username';
	        $sth = $this->db->prepare($sql);
	        $sth->execute(array(
	            ':username' => $barcode
	        ));
	        $results = $sth->fetchAll();
	
	        $view->firstname = $results[0]['firstname'];
	        $view->lastname = $results[0]['lastname'];
	        return $view;
        }
    }

    /**
     * "Retrieve accounts" action
     */
    public function homeAction()
    {
        $this->setupDatabase();

        $config = $this->getConfig();

	    $sql = 'SELECT * FROM user;';
	    $sth = $this->db->prepare($sql);
	    $sth->execute();
	    $results = $sth->fetchAll();

        $view = $this->createViewModel();
        $view->setTemplate('admin/pin/retrieve');
        $view->request = $this->getRequest()->getPost();
        $view->results = $results;
        return $view;
    }

    /**
     * "Update account" action
     */
    public function updateAction()
    {
        $this->setupDatabase();

        $barcode = $this->params()->fromQuery('barcode');

        $view = $this->createViewModel();
        $view->setTemplate('admin/pin/update');
        $view->barcode = $barcode;

        $sql = 'SELECT * FROM user WHERE username = :username';
        $sth = $this->db->prepare($sql);
        $sth->execute(array(
            ':username' => $barcode
        ));
        $results = $sth->fetchAll();

        $view->firstname = $results[0]['firstname'];
        $view->lastname = $results[0]['lastname'];
        $view->pin = $results[0]['password'];

        // Try to update this PIN in VuFind.
        if (!is_null($this->params()->fromPost('submit', null))) {
            if ($this->params()->fromPost('pin1') == '') {
                $this->flashMessenger()->setNamespace('error')->addMessage("You must enter a PIN. Please try again.");
            } else if ($this->params()->fromPost('pin1') != $this->params()->fromPost('pin2')) {
                $this->flashMessenger()->setNamespace('error')->addMessage("PINs must match. Please try again.");
            } else {
	            $sql = 'UPDATE user SET password = :password WHERE username = :username';
	            $sth = $this->db->prepare($sql);
	            $r = $sth->execute(array(
	                ':username' => $barcode,
                    ':password' => $this->params()->fromPost('pin1')
	            ));
                $affected_rows = $sth->rowCount();

                if ($r && $affected_rows == 1) {
                    $this->flashMessenger()->setNamespace('info')->addMessage(sprintf("PIN updated for %s.", $barcode));
                    return $this->forwardTo('Pin', 'Home');
                } else {
                    $this->flashMessenger()->setNamespace('error')->addMessage("There was a problem updating this record. Please contact support.");
                    return $view;
                }
            }
        }
        return $view;
    }
}
