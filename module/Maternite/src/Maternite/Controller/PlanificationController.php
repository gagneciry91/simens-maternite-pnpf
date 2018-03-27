<?php
namespace Maternite\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Json\Json;
//use Zend\Form\Form;
use Zend\Form\View\Helper\FormRow;
//use Zend\Form\View\Helper\FormInput;
use Maternite\View\Helpers\DateHelper;
use Maternite\Form\PatientForm;
use Zend\View\Model\ViewModel;
//use Zend\Db\Sql\Sql;
use Maternite\Form\AjoutDecesForm;
use Maternite\Form\planification\AdmissionForm;
use Maternite\Form\planification\ConsultationForm;
use Maternite;
//use Maternite\Form\planification\PartoForm;
use Zend\Form\View\Helper\FormTextarea;
use Zend\Form\View\Helper\FormHidden;
use Maternite\Form\planification\LibererPatientForm;
use Zend\Form\View\Helper\FormText;
use Zend\Form\View\Helper\FormSelect;
use Maternite\View\Helpers\DocumentPdf;
use Maternite\View\Helpers\DemandeExamenPdf;
use Maternite\View\Helpers\OrdonnancePdf;
use Maternite\View\Helpers\CertificatPdf;
use Maternite\View\Helpers\ProtocoleOperatoirePdf;
use Maternite\View\Helpers\TraitementChirurgicalPdf;
use Maternite\View\Helpers\TraitementInstrumentalPdf;
use Maternite\View\Helpers\RendezVousPdf;
use Maternite\View\Helpers\TransfertPdf;
use Maternite\View\Helpers\HospitalisationPdf;


class PlanificationController extends AbstractActionController{
	protected $controlDate;
	protected $patientTable;
	protected $evacuationTable;
	protected $consultationTable;
	protected $formPatient;
	protected $formAdmission;
	protected $type_admissionTable;
	
	
	public function getPatientTable() {
		if (! $this->patientTable) {
			$sm = $this->getServiceLocator ();
			$this->patientTable = $sm->get ( 'Maternite\Model\PatientTable' );
		}
		return $this->patientTable;
	}
	public function getForm() {
		if (! $this->formPatient) {
			$this->formPatient = new PatientForm();
		}
		return $this->formPatient;
	}
	public function getConsultationTable()
	{
		if (!$this->consultationTable) {
			$sm = $this->getServiceLocator();
			$this->consultationTable = $sm->get('Maternite\Model\ConsultationTable');
		}
		return $this->consultationTable;
	}
	
	//ajouter un nouveau patient
	
	public function ajouterAction() {
	
		$this->layout ()->setTemplate ( 'layout/planification' );
		
		$formAdmission = new AdmissionForm();
		
		$form = $this->getForm ();		
		
		$patientTable = $this->getPatientTable();
		//var_dump('tests'); exit();
		$form->get('NATIONALITE_ORIGINE')->setvalueOptions($patientTable->listeDeTousLesPays());
		$form->get('NATIONALITE_ACTUELLE')->setvalueOptions($patientTable->listeDeTousLesPays());
		$data = array('NATIONALITE_ORIGINE' => 'Sénégal', 'NATIONALITE_ACTUELLE' => 'Sénégal');
	
		$form->populateValues($data);
	
		return new ViewModel ( array (
				'form' => $form
		) );
	
	}
	
public function admissionAction() {
		$layout = $this->layout ();
	
		$layout->setTemplate ( 'layout/planification' );
		$user = $this->layout()->user;	
		$idService = $user ['IdService'];
        //INSTANCIATION DU FORMULAIRE D'ADMISSION
		$formAdmission = new AdmissionForm();	
		$pat = $this->getPatientTable ();
		
		if ($this->getRequest ()->isPost ()) {							
			$today = new \DateTime ();
			$dateAujourdhui = $today->format( 'Y-m-d' );
			$id = ( int ) $this->params ()->fromPost ( 'id', 0 );
			$donnee_ant = $pat->getInfoPatientAmise( $id );
			
			//MISE A JOUR DE L'AGE DU PATIENT
			$personne = $this->getPatientTable()->miseAJourAgePatient($id);
			//*******************************
	
			//Verifier si le patient a un rendez-vous et si oui dans quel service et a quel heure
			$RendezVOUS = $pat->verifierRV($id, $dateAujourdhui);
			
			$unPatient = $pat->getInfoPatient( $id );
			
	
			$photo = $pat->getPhoto ( $id );
		
			$date = $unPatient['DATE_NAISSANCE'];
			if($date){ $date = $this->convertDate ( $unPatient['DATE_NAISSANCE'] ); }else{ $date = null;}

			$html  = "<div style='width:100%;'>";
			
			$html  = "<div style='width:100%;'>";
				
 			$html .= "<div style='width: 18%; height: 190px; float:left;'>";
 			$html .= "<div id='photo' style='float:left; margin-left:40px; margin-top:10px; margin-right:30px;'> <img style='width:105px; height:105px;' src='../img/photos_patients/" . $photo . "' ></div>";
 			$html .= "<div style='margin-left:60px; margin-top: 150px;'> <div style='text-decoration:none; font-size:14px; float:left; padding-right: 7px; '>Age:</div>  <div style='font-weight:bold; font-size:19px; font-family: time new romans; color: green; font-weight: bold;'>" . $unPatient['AGE'] . " ans</div></div>";
 			$html .= "</div>";
				
			$html .= "<div id='vuePatientAdmission' style='width: 70%; height: 190px; float:left;'>";
			$html .= "<table style='margin-top:0px; float:left; width: 100%;'>";
				
			$html .= "<tr style='width: 100%;'>";
			
			$html .= "<td style='width: 19%; vertical-align: top;'><a style='text-decoration:underline; font-size:12px;'>Numero dossier:</a><br><div style='width: 150px; max-width: 160px; height:40px; overflow:auto; margin-bottom: 3px;'><p style='font-weight:bold; font-size:17px;'>" . $unPatient['NUMERO_DOSSIER'] . "</p></div></td>";
			$html .= "<td style='width: 29%; vertical-align: top;'><a style='text-decoration:underline; font-size:12px;'>Date de naissance:</a><br><div style='width: 95%; max-width: 250px; height:40px; overflow:auto; margin-bottom: 3px;'><p style=' font-weight:bold; font-size:17px;'>" . $unPatient['DATE_NAISSANCE'] . "</p></div></td>";

			$html .= "<td style='width: 23%; vertical-align: top;'><a style='text-decoration:underline; font-size:12px;'>T&eacute;l&eacute;phone:</a><br><div style='width: 95%; '><p style=' font-weight:bold; font-size:17px;'>" . $unPatient['TELEPHONE'] . "</p></div></td>";
				
			$html .= "<td style='width: 29%; '></td>";
			
				$html .= "</tr><tr style='width: 100%;'>";
				$html .= "<td style='vertical-align: top;'><a style='text-decoration:underline; font-size:12px;'>Pr&eacute;nom:</a><br><div style='width: 95%; max-width: 130px; height:40px; overflow:auto; margin-bottom: 3px;'><p style=' font-weight:bold; font-size:17px;'>" . $unPatient['PRENOM'] . "</p></div></td>";
				$html .= "<td style='width: 29%; vertical-align: top;'><a style='text-decoration:underline; font-size:12px;'>Lieu de naissance:</a><br><div style='width: 95%; max-width: 250px; height:40px; overflow:auto; margin-bottom: 3px;'><p style=' font-weight:bold; font-size:17px;'>" . $unPatient['LIEU_NAISSANCE'] . "</p></div></td>";
				$html .= "<td style='vertical-align: top;'><a style='text-decoration:underline; font-size:12px;'>Nationalit&eacute; actuelle:</a><br><div style='width: 95%; max-width: 135px; overflow:auto; '><p style=' font-weight:bold; font-size:17px;'>" . $unPatient['NATIONALITE_ACTUELLE']. "</p></td>";
				$html .= "<td style='vertical-align: top;'><a style='text-decoration:underline; font-size:12px;'>Email:</a><br><div style='width: 100%; max-width: 235px; height:40px; overflow:auto;'><p style='font-weight:bold; font-size:17px;'>" . $unPatient['EMAIL'] . "</p></div></td>";
					
				$html .= "</tr><tr style='width: 100%;'>";
				$html .= "<td style='vertical-align: top;'><a style='text-decoration:underline; font-size:12px;'>Nom:</a><br><div style='width: 95%; max-width: 235px; height:40px; overflow:auto; '><p style=' font-weight:bold; font-size:17px;'>" .  $unPatient['NOM'] . "</p></div></td>";
				$html .= "<td style='vertical-align: top;'><a style='text-decoration:underline; font-size:12px;'>Adresse:</a><br><div style='width: 97%; max-width: 250px; height:50px; overflow:auto; margin-bottom: 3px;'><p style=' font-weight:bold; font-size:17px;'>" . $unPatient['ADRESSE'] . "</p></div></td>";
				$html .= "<td style='vertical-align: top;'><a style='text-decoration:underline; font-size:12px;'>Sexe:</a><br><div style='width: 100%; max-width: 235px; height:40px; overflow:auto;'><p style='font-weight:bold; font-size:17px;'>" . $unPatient['SEXE'] . "</p></div></td>";
				
			
			$html .= "<td style='width: 30%; height: 50px;'>";
			
			if($RendezVOUS){
				$html .= "<span> <i style='color:green;'>
					        <span id='image-neon' style='color:red; font-weight:bold;'>Rendez-vous! </span> <br>
					        <span style='font-size: 16px;'>Service:</span> <span style='font-size: 16px; font-weight:bold;'> ". $pat->getServiceParId($RendezVOUS[ 'ID_SERVICE' ])[ 'NOM' ]." </span> <br>
					        <span style='font-size: 16px;'>Heure:</span>  <span style='font-size: 16px; font-weight:bold;'>". $RendezVOUS[ 'HEURE' ]." </span> </i>
			              </span>";
			}
				$html .= "</td>";
			$html .= "</tr>";
			$html .= "</table>";
			$html .= "</div>";
			
			$html .= "<div style='width: 12%; height: 190px; float:left;'>";
			$html .= "<div id='' style='color: white; opacity: 0.09; float:left; margin-right:10px; margin-left:5px; margin-top:5px;'> <img style='width:105px; height:105px;' src='../img/photos_patients/" . $photo . "'></div>";
			$html .= "</div>";
			


			$this->getResponse ()->getHeaders ()->addHeaderLine ( 'Content-Type', 'application/html; charset=utf-8' );
 			return $this->getResponse ()->setContent ( Json::encode ( $html ) );
 		}
 		//var_dump($formAdmission);exit();
		return array (
				'form' => $formAdmission
		);
	
	}
	
	
	public function enregistrementAction() {
	
		$user = $this->layout()->user;
		$id_employe = $user['id_personne']; //L'utilisateur connect�
	
		// CHARGEMENT DE LA PHOTO ET ENREGISTREMENT DES DONNEES
		if (isset ( $_POST ['terminer'] )||isset($_POST ['terminer_ad']))  // si formulaire soumis
		{
			$Control = new DateHelper();
			$form = new PatientForm();
			$Patient = $this->getPatientTable ();
			$today = new \DateTime ( 'now' );
			$nomfile = $today->format ( 'dmy_His' );
			$date_enregistrement = $today->format ( 'Y-m-d H:i:s' );
			$fileBase64 = $this->params ()->fromPost ( 'fichier_tmp' );
			$fileBase64 = substr ( $fileBase64, 23 );
	
			if($fileBase64){
				$img = imagecreatefromstring(base64_decode($fileBase64));
			}else {
				$img = false;
			}
	
			$date_naissance = $this->params ()->fromPost ( 'DATE_NAISSANCE' );
			if($date_naissance){ $date_naissance = $Control->convertDateInAnglais($this->params ()->fromPost ( 'DATE_NAISSANCE' )); }else{ $date_naissance = null;}
	
			$donnees = array(
					'LIEU_NAISSANCE' => $this->params ()->fromPost ( 'LIEU_NAISSANCE' ),
					'EMAIL' => $this->params ()->fromPost ( 'EMAIL' ),
					'NOM' => $this->params ()->fromPost ( 'NOM' ),
					'TELEPHONE' => $this->params ()->fromPost ( 'TELEPHONE' ),
					'NATIONALITE_ORIGINE' => $this->params ()->fromPost ( 'NATIONALITE_ORIGINE' ),
					'PRENOM' => $this->params ()->fromPost ( 'PRENOM' ),
					'PROFESSION' => $this->params ()->fromPost ( 'PROFESSION' ),
					'NATIONALITE_ACTUELLE' => $this->params ()->fromPost ( 'NATIONALITE_ACTUELLE' ),
					'DATE_NAISSANCE' => $date_naissance,
					'ADRESSE' => $this->params ()->fromPost ( 'ADRESSE' ),
					'SEXE' => $this->params ()->fromPost ( 'SEXE' ),
					'AGE' => $this->params ()->fromPost ( 'AGE' ),
					'DATE_MODIFICATION' => $today->format ( 'Y-m-d' ),
					'NOM_CONJOINT' => $this->params ()->fromPost ( 'NOM_CONJOINT' ),
					'PRENOM_CONJOINT' => $this->params ()->fromPost ( 'PRENOM_CONJOINT' ),
					'PROFESSION_CONJOINT' => $this->params ()->fromPost ( 'PROFESSION_CONJOINT' ),
			);
				
			//var_dump($donnees); exit();
				
			if ($img != false) {
	
				$donnees['PHOTO'] = $nomfile;
				//ENREGISTREMENT DE LA PHOTO
				imagejpeg ( $img, 'C:\wamp64\www\simens-maternite\public\img\photos_patients\\' . $nomfile . '.jpg' );
				//ENREGISTREMENT DES DONNEES
					
				$Patient->addPatient ( $donnees , $date_enregistrement , $id_employe );
				if (isset($_POST ['terminer'])){
					return $this->redirect ()->toRoute ( 'planification', array (
							'action' => 'liste-patient'
					) );}
					if (isset($_POST ['terminer_ad'])){
						return $this->redirect ()->toRoute ( 'planification', array (
								'action' => 'admission'
						));
							
					}
			} else {
	
				// On enregistre sans la photo
				//var_dump($donnees); exit();
				$Patient->addPatient ( $donnees , $date_enregistrement , $id_employe );
				//var_dump($donnees); exit();
				if (isset($_POST ['terminer'])){
					return $this->redirect ()->toRoute ( 'planification', array (
							'action' => 'liste-patient'
					) );}
					if (isset($_POST ['terminer_ad'])){
						return $this->redirect ()->toRoute ( 'planification', array (
								'action' => 'admission'
						) );
							
							
					}
						
						
			}
		}
	
		return $this->redirect ()->toRoute ( 'planification', array (
				'action' => 'liste-patient'
		) );
	}
	public function planificationAction(){
		$this->layout()->setTemplate('layout/planification');
		$user = $this->layout()->user;
		$idService = $user ['IdService'];
	
		$lespatients = $this->getConsultationTable()->listeDesPlanification($idService);		//var_dump("ggff"); exit();
		
		// RECUPERER TOUS LES PATIENTS AYANT UN RV aujourd'hui
		$tabPatientRV = $this->getConsultationTable()->getPatientsRV($idService);
		//var_dump($lespatients); exit();
		return new ViewModel (array(
				'donnees' => $lespatients,
				'tabPatientRV' => $tabPatientRV
		));
	
	
	
	}
	
	public function complementPlanificationAction()
	{
		$this->layout()->setTemplate('layout/planification');
		$user = $this->layout()->user;
		$IdDuService = $user ['IdService'];
		$id_medecin = $user ['id_personne'];		
		
		//$this->getDateHelper();
		$id_pat = $this->params()->fromQuery('id_patient', 0);
		$id = $this->params()->fromQuery('id_cons');
		$inf=$this->getConsultationTable()->infpat($id_pat, $id);
		
		$id_admi = $this->params()->fromQuery('id_admission', 0);
		
		$liste = $this->getConsultationTable()->getInfoPatient($id_pat);
		$id_admission=$liste['id_admission'];
		$consult = $this->getConsultationTable()->getConsult($id);
		
		$image = $this->getConsultationTable()->getPhoto($id_pat);
		$form = new ConsultationForm ();
		
	//	$this->getDateHelper();		
		//var_dump('test'); exit();
		
		
		return array(
				'lesdetails' => $liste,
				'id_cons' => $id,
		
				'image' => $image,
				'form' => $form,
				'heure_cons' => $consult->heurecons,
				'dateonly' => $consult->dateonly,
				//'liste_med' => $listeMedicament,
					
		
				/* 'temoin' => $bandelettes ['temoin'],
				'listeForme' => $listeForme,
				'listetypeQuantiteMedicament' => $listetypeQuantiteMedicament,
				'donneesAntecedentsPersonnels' => $donneesAntecedentsPersonnels,
				'donneesAntecedentsFamiliaux' => $donneesAntecedentsFamiliaux,
				//'liste' => $listeConsultation,
				'nbDiagnostics'=> $infoDiagnostics->count(),
				//'resultRV' => $resultRV,
						//'listesDecesMaternel'=>$listesDecesMaternel,
				//'listeDesExamensMorphologiques' => $listeDesExamensMorphologiques,
				'listeAntMed' => $listeAntMed,
				'antMedPat' => $antMedPat,
				'nbAntMedPat' => $antMedPat->count(), */
		
		);
	}
	
	public function visiteAction()
	{
		$id_cons = $this->params()->fromPost('id_cons');
		$complication = $this->params()->fromPost('comp');
		$notesComp = $this->params()->fromPost('notesComp');
	
		$this->conclusionTable()->saveComplication($id_cons, $complication, $notesComp);
	
		$this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/html');
		return $this->getResponse()->setContent(Json::encode());
	}
	public function declarerDecesAction() {
		$this->layout ()->setTemplate ( 'layout/planification' );
	
		//INSTANCIATION DU FORMULAIRE DE DECES
		$ajoutDecesForm = new AjoutDecesForm ();
	
		if ($this->getRequest ()->isPost ()) {
			$id = ( int ) $this->params ()->fromPost ( 'id', 0 );
	
			$personne = $this->getPatientTable()->miseAJourAgePatient($id);
			//*******************************
			//*******************************
			//*******************************
			$pat = $this->getPatientTable ();
			$unPatient = $pat->getInfoPatient ( $id );
			$photo = $pat->getPhoto ( $id );
	
			$date = $unPatient['DATE_NAISSANCE'];
			if($date){ $date = $this->convertDate ($date); }else{ $date = null;}
	
			$html = "<div style='float:left;' ><div id='photo' style='float:left; margin-right:20px; margin-bottom: 10px;'> <img  src='../img/photos_patients/" . $photo . "'  style='width:105px; height:105px;'></div>";
			$html .= "<div style='margin-left:6px;'> <div style='text-decoration:none; font-size:14px; float:left; padding-right: 7px; '>Age:</div>  <div style='font-weight:bold; font-size:19px; font-family: time new romans; color: green; font-weight: bold;'>" . $unPatient['AGE'] . " ans</div></div></div>";
	
	
			$html .= "<table>";
	
			$html .= "<tr>";
			$html .= "<td><a style='text-decoration:underline; font-size:12px;'>Nom:</a><br><p style='width:280px; font-weight:bold; font-size:17px;'>" . $unPatient['NOM'] . "</p></td>";
			$html .= "</tr><tr>";
			$html .= "<td><a style='text-decoration:underline; font-size:12px;'>Pr&eacute;nom:</a><br><p style='width:280px; font-weight:bold; font-size:17px;'>" . $unPatient['PRENOM'] . "</p></td>";
			$html .= "</tr><tr>";
			$html .= "<td><a style='text-decoration:underline; font-size:12px;'>Date de naissance:</a><br><p style='width:280px; font-weight:bold; font-size:17px;'>" . $date . "</p></td>";
			$html .= "</tr><tr>";
			$html .= "<td><a style='text-decoration:underline; font-size:12px;'>Adresse:</a><br><p style='width:280px; font-weight:bold; font-size:17px;'>" . $unPatient['ADRESSE'] . "</p></td>";
			$html .= "</tr><tr>";
			$html .= "<td><a style='text-decoration:underline; font-size:12px;'>T&eacute;l&eacute;phone:</a><br><p style='width:280px; font-weight:bold; font-size:17px;'>" . $unPatient['TELEPHONE'] . "</p></td>";
			$html .= "</tr>";
	
			$html .= "</table>";
			$this->getResponse ()->getHeaders ()->addHeaderLine ( 'Content-Type', 'application/html; charset=utf-8' );
			return $this->getResponse ()->setContent ( Json::encode ( $html ) );
		}
		return array (
				'form' => $ajoutDecesForm
		);
	}
	
}
