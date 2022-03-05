<?php

namespace App\Http\Controllers\Crons;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\ColumnDefinition;
use Carbon\Carbon;
use Hash;

use App\Models\People;
use App\Models\User;

class CronPeopleController extends Controller
{
    public function __construct(){
        $this->error = 0;
        $this->msg = '';
        $this->separador = env('IMPORT_DELIMITER', ',');
        $this->pathLayout = 'Interface/';
        $this->fileName = env('IMPORT_FILENAME', 'layout.csv'); //Nombre del archivo a importar

        $this->fileHeaders = array(
            'clave',
            'nombre',
            'paterno',
            'materno',
            'fuente',
            'rfc',
            'curp',
            'nss',
            'correoempresa',
            'correopersonal',
            'nacimiento',
            'sexo',
            'civil',
            'telefono',
            'extension',
            'celular',
            'ingreso',
            'fechapuesto',
            'jefe',
            //'direccion',
            //'departamento',
            //'seccion',
            //'puesto',
            //'grado',
            //'region',
            //'sucursal',
            //'idempresa',
            //'empresa',
            //'division',
            //'marca',
            //'centro',  
            //'checador',
            'turno',
            'tiponomina',
            'clavenomina',
            'nombrenomina',
            //'generalista',
            'relacion',
            'contrato',
            'horario',
            'jornada',
            'calculo',
            'vacaciones',
            'flotante',
            'base',
            'rol',
            //'password',
            'extra1',
            'extra2',
            'extra3',
            'extra4',
            'extra5',
            //'fecha',
            'version'
        );
    
        $this->campos = array();
        $this->table = 'peoples'; //Cambiar el nombre de la tabla
        $this->path = Storage::disk('local')
                    ->getDriver()
                    ->getAdapter()
                    ->getPathPrefix();

        //Configuraciones adicionales
        $this->checkIfHasHeaders = true;
        $this->allowEmptyFile = false;
        $this->usesEmployeeModel = true;
        $this->setToNull_ifCantConvert = true;
        $this->firstDetectedSource = null;
        $this->createNewUsers = true;
        $this->canNotBeEmpty = ['correoempresa', 'rfc'];
        $this->pivotKey = 'rfc';
        $this->personalFieldToUser = ['email' => 'correoempresa'];
        $this->fieldsToEncryp = ['password']; //Campos a encriptar
        $this->unsetIfEmpty = ['password']; //Eliminar campos que vengan vacios, para no actulizar con información en blanco
        $this->defaultPassword = 'Artemisa2022$';
        $this->except_model_fields = ['rfc'];
    }

    /**
     * Funcion principal del cron, la cual es llamada por el archivo de rutas
     *
     * @return void
     */
    public function mainFunction(){
        $data = $this->readFile();
        $this->loadColumnTypes();
        if($this->allowEmptyFile || count($data) > 0){
            $data = $this->formatData($data);
            /*$data = $this->fillHierarchyInUsers($data);
            $hierarchy = $this->loadDirections($data);
            $hierarchy = $this->loadDepartments($data, $hierarchy);
            $hierarchy = $this->loadAreas($data, $hierarchy);
            $hierarchy = $this->loadJobPositions($data, $hierarchy);
            $enterprises = $this->loadEnterprise($data);
            $regions = $this->loadRegions($data);
            $this->loadVacationBenefit();*/
            //$this->loadInformationToDatabase($data, $hierarchy, $enterprises, $regions);
            $this->loadInformationToDatabase($data);
        }
        //$this->sendReportEmail();
    }

    /**
     * Leeremos el archivo usando la definición de variables en el construnctor
     * Se validaran ciertas cosas formateandolo todo en un array de datos
     * 
     * Nota: Si el separadador no es el correcto el archivo funciona pero se acomoda mal 
     *
     * @return void
     */
    public function readFile(){
        $File = fopen($this->path . $this->pathLayout . $this->fileName, 'r');
        $i = 0;
        $csvData = [];
        while ($line = fgetcsv($File, 0, $this->separador)){
            $data = [];
            if($this->checkIfHasHeaders){
                if($this->check_if_firstrow_match_headers($line)){
                    $this->checkIfHasHeaders = false;
                } else {
                    $this->msg = $this->msg . '<br/>Headers distintos';
                    $this->error++;
                }
                if(!$this->check_if_delimiter_is_correct($line)){
                    $this->msg = $this->msg . '<br/>Posible fallo con el delimitador: ' . $line;
                    $this->error++;
                }
                //Leemos toda las lineas y las acomodamos ordenadas
                for($i = 0; $i < count($line); $i++){
                    $data[$this->fileHeaders[$i]] = trim($line[$i]);
                }
                $csvData[] = $data;
            } else {
                if(!$this->check_if_delimiter_is_correct($line)){
                    $this->msg = $this->msg . '<br/>Posible fallo con el delimitador: ' . $line;
                    $this->error++;
                }
                //Leemos toda las lineas y las acomodamos ordenadas
                for($i = 0; $i < count($line); $i++){
                    $data[$this->fileHeaders[$i]] = trim($line[$i]);
                }
                $csvData[] = $data;
            }
        }
        if(count($csvData) > 0){
            return $csvData;
        }else if($this->allowEmptyFile){
            return [];
        }
        else{
            $this->msg = $this->msg . '<br/>No hay datos.';
        }
    }

    /**
     * Revisa si la primer fila en el archivo cuadra con las cabeceras definidas
     * en la var $this->fileHeaders, si estas no cuadran falla
     *
     * @param  mixed $data
     *
     * @return bool
     */
    public function check_if_firstrow_match_headers($data){
        $fileHeaders = [];
        $readFileHeaders = [];
        /*
         * Convertimos a MAYUSCULAS las cabeceras para comparar el orden y la cantidad
         */
        foreach($this->fileHeaders as $header){
            $fileHeaders[] = strtoupper($header);
        }
        foreach($data as $header){
            $readFileHeaders[] = strtoupper($header);
        }
        for ($i = 0; $i < count($fileHeaders); $i++) { 
            if($fileHeaders[$i] !== $readFileHeaders[$i]){
                return false;
            }
        }
        return true;
    }

     /**
     * Comprueba si el delimitador proporcionado es correcto comparando el tamaño
     * del arreglo obtenido contra el total de cabeceras definidas en @var $this->fileHeaders
     *
     * @param  array $data
     *
     * @return bool
     */
    public function check_if_delimiter_is_correct($data){
        if(count($data) == count($this->fileHeaders)){
            return true;
        }
        return false;
    }

    /**
     * Carga los tipos de columnas a partir de la estructura de la base de datos
     * formando un arreglo donde la llave es el nombre y el valor es el tipo de dato
     *
     * @return void
     */
    public function loadColumnTypes(){
        $array = [];
        if($this->usesEmployeeModel){
            $tableName = with(new People)->getTable();
        }else{
            $tableName = with(new User)->getTable();
        }
        foreach($this->fileHeaders as $header){ 
            if(Schema::hasColumn($tableName, $header)){
                $type = \DB::getSchemaBuilder()->getColumnType($tableName, $header);
                $array[$header] = $type;
            }else{
                $array[$header] = null;
            }
        }
        $this->columnTypes = $array;
    }

    /**
     * Formatea la información generada por la función readFile()
     * tomando la estructura de la base de datos, intenta converitir la información 
     * al tipo de dato asignado, si falla quita el registro y lo guarda para reportes
     * 
     * Nota: Tiene mas tipos de validaciones internas definidias en el constructor 
     *
     * @param  array $data
     *
     * @return array
     */
    public function formatData($data){
        $problems = [];
        foreach($data as $pos => &$row){
            foreach($row as $key => &$field){
                switch($this->columnTypes[$key]) {
                    case "string":
                        if(in_array($key, $this->canNotBeEmpty) && empty($field)){
                            $this->msg = $this->msg . '<br/> El campo de la columna _' . $key. '_ no puede venir vacio. Fila: ' . strval($pos + 1);
                            unset($data[$pos]);
                        }
                        break;
                    case "integer":
                        if(is_numeric($field)){
                            $field = intval($field);
                        }else{
                            if($this->setToNull_ifCantConvert){
                                $field = null;
                            }else{
                                $this->msg = $this->msg . '<br/> Este campo en la columna _' . $key. '_ es de tipo INTEGER y el valor (' . $field .') no se puede convertir correctamente. Fila: ' . strval($pos + 1);
                                unset($data[$pos]);
                            }
                        }
                        break;
                    case "date":
                        try {
                            if(empty($field)){
                                $field = null;
                            }else{
                                $field = preg_replace('"/"', '-', $field, -1, $count);
                                $date = new Carbon($field);
                                $field = $date->format('Y-m-d');
                            }
                        } catch (\Throwable $th) {
                            if($this->setToNull_ifCantConvert){
                                $field = null;
                            }else{
                                $this->msg = $this->msg . '<br/> Este campo en la columna _' . 
                                    $key. '_ es de tipo DATE y el valor (' . 
                                    $field . ') no se puede convertir correctamente. Fila: ' .
                                    strval($pos + 1);
                                unset($data[$pos]);
                            }
                        }
                        break;
                    default:
                        break;
                }
                //if($this->workOnlyWithSameSource && is_null($this->firstDetectedSource)){
                  //  $this->firstDetectedSource = $row['fuente'];
                //}

                if(in_array($key, $this->unsetIfEmpty) && empty($field)){
                    unset($row[$key]);
                }//else if(in_array($key, $this->fieldsToEncryp)){
                    //$field = Hash::make(trim($field));
                //}
            }
        }
        return $data;
    }

    /**
     * Redirige el trabajo a la funcion correspondiente dependiendo si el cron
     * maneja 1 o los 2 modelos que manejamos
     * 
     * NOTA IMPORTANTE: La parte de loadSigleModel() no esta bien probada ya que no se usa con frecuencia
     *
     * @param  array $data
     * @param  array $jobs
     * @param  array $enterprise
     *
     * @return void
     */
    //public function loadInformationToDatabase($data, $jobs, $enterprise, $regions){
    public function loadInformationToDatabase($data){
        if($this->usesEmployeeModel){
            //$this->loadWithBothModels($data, $jobs, $enterprise, $regions);
            $this->loadWithBothModels($data);
        }else{
            $this->loadSigleModel($data, $jobs);
        }
    }

    /**
     * Aqui es donde hacemos todas las inserciones y actualizaciones a la base de datos
     * utilizando los dos modelos
     * 
     * Nota: Tiene multiples configuraciones, revisar a fondo.
     *
     * @param  array $data
     * @param  array $hierachy
     * @param  array $enterprise
     *
     * @return void
     */
    //public function loadWithBothModels($data, $hierachy, $enterprise, $regions){
    public function loadWithBothModels($data){
        unset($data[0]);
        $personalUpdated = [];
        $personalCreated = [];
        foreach ($data as $key => $user) {
            $personal = People::withTrashed()
            ->where($this->pivotKey, $user[$this->pivotKey])
            ->first();
            if($personal){
                if(!is_null($this->firstDetectedSource)){
                    if($user['fuente'] != $this->firstDetectedSource){
                        $this->update_errors[] = $this->formatMessageWithPersonnelData($user, 'FUENTE');
                        continue;
                    }
                }
                if($this->activatedIfNeeded($personal)){
                    $beforePersonal = $personal->replicate();
                    /*if(!$this->verfyUniqueFields($user, $personal)){
                        $this->update_errors[] = $this->formatMessageWithPersonnelData($user, 'DUPLICATE_FIELDS');
                        continue;
                    }*/
                    
                    $personal = $this->changePersonalData($personal, $user);
                    //$personal->job_position_id = $this->getJobInHierarchy($user, $hierachy);
                    //$personal->enterprise_id = $this->issetOrNull($enterprise, $user['empresa']);
                    //$personal->region_id = $this->issetOrNull($regions, $user['region']);
    
                    $hasChanged = $personal->isDirty();
                    \DB::beginTransaction();
                    try {
                        $personal->save();
                    } catch (\Throwable $th) {
                        \DB::rollback();
                        $this->update_errors[] = $this->formatMessageWithPersonnelData($user, 'EXCEPTION', $th->getMessage());
                        continue;
                    }
                    try{
                        if($personal->user){
                            $personal->user()->update([
                                'name' => $personal->nombre,
                                'last_name' => $personal->paterno . ' ' . $personal->materno,
                                'email' => $personal->{$this->personalFieldToUser['email']},
                                //'password' => $personal->password,
                            ]);
                        }else{
                            User::create([
                                'role_id' => 2,
                                'people_id' => $personal->id,
                                'name' => $personal->nombre,
                                'last_name' => $personal->paterno . ' ' . $personal->materno,
                                'email' => $personal->{$this->personalFieldToUser['email']},
                                'password' => Hash::make(trim($this->defaultPassword)),
                                //'active' => 1,
                            ]);
                            $this->created_users[] = $this->formatMessageWithPersonnelData($user);
                            $this->newUsers[] = $_user;
                        }
                    } catch (\Throwable $th) {
                        //dd($th->getMessage());
                        \DB::rollback();
                        $this->update_errors[] = $this->formatMessageWithPersonnelData($user, 'EXCEPTION', $th->getMessage());
                        continue;
                    }
                    //if(!$this->saveChangesToLog($personal, $beforePersonal)){
                        //Aqui truena si no pudimos guardar el LOG
                        // continue;
                    //}
                    //$this->manageVacations($personal, $user);
                    \DB::commit();
                    if($hasChanged){
                        $this->updated_users[] = $this->formatMessageWithPersonnelData($user);
                    }
                    $personalUpdated[] = $personal->id;
                } else {
                    $beforePersonal = $personal->replicate();
                    /*if(!$this->verfyUniqueFields($user, $personal)){
                        $this->update_errors[] = $this->formatMessageWithPersonnelData($user, 'DUPLICATE_FIELDS');
                        continue;
                    }*/
                    
                    $personal = $this->changePersonalData($personal, $user);
                    //$personal->job_position_id = $this->getJobInHierarchy($user, $hierachy);
                    //$personal->enterprise_id = $this->issetOrNull($enterprise, $user['empresa']);
                    //$personal->region_id = $this->issetOrNull($regions, $user['region']);

                    $hasChanged = $personal->isDirty();
        
                    \DB::beginTransaction();
                    try {
                        $personal->save();
                        //dd("actualizo");
                    } catch (\Throwable $th) {
                        //dd($th->getMessage());
                        \DB::rollback();
                        $this->update_errors[] = $this->formatMessageWithPersonnelData($user, 'EXCEPTION', $th->getMessage());
                        continue;
                    }
                    try{
                        dd($personal->user);
                        if($personal->user){
                            $personal->user()->update([
                                'name' => $personal->nombre,
                                'last_name' => $personal->paterno . ' ' . $personal->materno,
                                'email' => $personal->{$this->personalFieldToUser['email']},
                                //'password' => $personal->password,
                            ]);
                        }else{
                            User::create([
                                'role_id' => 2,
                                'people_id' => $personal->id,
                                'name' => $personal->nombre,
                                'last_name' => $personal->paterno . ' ' . $personal->materno,
                                'email' => $personal->{$this->personalFieldToUser['email']},
                                'password' => Hash::make(trim($this->defaultPassword)),
                                //'active' => 1,
                            ]);
                            $this->created_users[] = $this->formatMessageWithPersonnelData($user);
                            $this->newUsers[] = $_user;
                        }
                    } catch (\Throwable $th) {
                        dd($th->getMessage());
                        \DB::rollback();
                        $this->update_errors[] = $this->formatMessageWithPersonnelData($user, 'EXCEPTION', $th->getMessage());
                        continue;
                    }
                    //if(!$this->saveChangesToLog($personal, $beforePersonal)){
                        //Aqui truena si no pudimos guardar el LOG
                        // continue;
                    //}
                    //$this->manageVacations($personal, $user);
                    \DB::commit();
                    if($hasChanged){
                        $this->updated_users[] = $this->formatMessageWithPersonnelData($user);
                    }
                    $personalUpdated[] = $personal->id;
                }
            }else{
                if(!is_null($this->firstDetectedSource)){
                    if($user['fuente'] != $this->firstDetectedSource){
                        $this->create_errors[] = $this->formatMessageWithPersonnelData($user, 'FUENTE');
                        continue;
                    }
                }
                /*if(!$this->verfyUniqueFields($user)){
                    $this->create_errors[] = $this->formatMessageWithPersonnelData($user, 'DUPLICATE_FIELDS');
                    continue;
                }*/
                \DB::beginTransaction();
                //$user['job_position_id'] = $this->getJobInHierarchy($user, $hierachy);
                //$user['enterprise_id'] = $this->issetOrNull($enterprise, $user['empresa']);
                //$user['region_id'] = $this->issetOrNull($regions, $user['region']);
                //unset($user['puesto']);
                //unset($user['empresa']);
                //unset($user['region']);
                //$user['password'] = Hash::make($this->defaultPassword);
                try {
                    $personal = People::create($user);
                    //dd($personal);
                } catch (\Throwable $th) {
                    //dd($th->getMessage());
                    DB::rollback();
                    $this->create_errors[] = $this->formatMessageWithPersonnelData($user, 'EXCEPTION', $th->getMessage());
                    continue;                    
                }
                if($this->createNewUsers){
                    try {
                        $_user = User::create([
                            'role_id' => 2,
                            'people_id' => $personal->id,
                            'name' => $personal->nombre,
                            'last_name' => $personal->paterno . ' ' . $personal->materno,
                            'email' => $personal->{$this->personalFieldToUser['email']},
                            'password' => Hash::make(trim($this->defaultPassword)),
                            //'active' => 1,
                        ]);
                    } catch (\Throwable $th) {
                        $this->create_errors[] = $this->formatMessageWithPersonnelData($user, 'EXCEPTION', $th->getMessage());
                        \DB::rollback();
                        continue;
                    }
                }
                //$this->manageVacations($personal, $user);
                \DB::commit();
                $personalCreated[] = $personal->id;
                $this->created_users[] = $this->formatMessageWithPersonnelData($user);
                $this->newUsers[] = $_user;
            }
        }
        $personalDeleted = 0;
        //$personalNotToDelete = array_merge($personalUpdated, $personalCreated);
        $personalNotToDelete = array_merge($personalCreated);
        if(count($personalNotToDelete) > 0){
            $queryPersonals = People::whereNotIn('id', $personalNotToDelete);
            $personalDeleted = $queryPersonals->count();
            $personals = $queryPersonals->get();
            foreach ($personals as $personal) {
                try {
                    \DB::beginTransaction();
                    $user = $personal->toArray();
                    if($personal->user)
                        $personal->user->delete();
                    $personal->delete();
                    $this->deleted_users[] = $this->formatMessageWithPersonnelData($user);
                    \DB::commit();
                } catch (\Throwable $th) {
                    \DB::rollback();
                    $this->delete_errors = $this->formatMessageWithPersonnelData($user, 'EXCEPTION', $th->getMessage());
                }    
            }   
        }
    }

    /**
     * Aqui es donde hacemos todas las inserciones y actualizaciones a la base de datos
     * utilizando un unico modelo
     * 
     * ADVERTENCIA: NO SE HA TERMINADO, FALLARA SEGURAMENTE
     *
     * @param  mixed $data
     *
     * @return void
     */
    public function loadSigleModel($data){
        /*$personalUpdated = [];
        $personalCreated = [];
        foreach ($data as $key => $user) {
            $personal = User::withTrashed()
            ->where($this->pivotKey, $user[$this->pivotKey])
            ->first();
            
            if($personal){
                if(!is_null($this->firstDetectedSource)){
                    if($user['fuente'] != $this->firstDetectedSource){
                        $this->update_errors[] = 'Se quiere asignar la fuente('.$user['fuente'].') que no corresponde a la primera(' . $this->firstDetectedSource . ') con el ' . strtoupper($this->pivotKey) . ': ' . $user[$this->pivotKey];
                        continue;
                    }
                }
                if($this->activatedIfNeeded($personal)){
                    $beforePersonal = $personal->replicate();
                    if(!$this->verfyUniqueFields($user, $personal)){
                        $this->update_errors[] = 'Ya existe un usuario con ese correo _' . $user['correoempresa'] . '_ con el ' . strtoupper($this->pivotKey) . ': ' . $user[$this->pivotKey];
                        continue;
                    }
                    $personal = $this->changePersonalData($personal, $user);
                    \DB::beginTransaction();
                    try {
                        $personal->save();
                    } catch (\Throwable $th) {
                        \DB::rollback();
                        $this->update_errors[] = 'No se pudo actualizar el USUARIO con el campo('.$user[$this->pivotKey].')  |  ' . $th->getMessage();
                        continue;
                    } 
                    if(!$this->saveChangesToLog($personal, $beforePersonal)){
                        //Aqui truena si no pudimos guardar el LOG
                        // continue;
                    }
                    \DB::commit();

                    $personalUpdated[] = $personal->id;
                };
            }else{
                if(!is_null($this->firstDetectedSource)){
                    if($user['fuente'] != $this->firstDetectedSource){
                        $this->create_errors[] = 'Se quiere asignar la fuente('.$user['fuente'].') que no corresponde a la primera(' . $this->firstDetectedSource . ') con el ' . strtoupper($this->pivotKey) . ': ' . $user[$this->pivotKey];
                        continue;
                    }
                }
                if(!$this->verfyUniqueFields($user)){
                    $this->create_errors[] = 'Ya existe un usuario con ese correo _' . $user['correoempresa'] . '_ con el ' . strtoupper($this->pivotKey) . ': ' . $user[$this->pivotKey];
                    continue;
                }
                \DB::beginTransaction();
                $user['password'] = Hash::make($this->defaultPassword);
                try {
                    $personal = User::create($user);    
                } catch (\Throwable $th) {
                    DB::rollback();
                    $this->create_errors[] = 'No se pudo crear el EMPLEADO con el campo('.$user[$this->pivotKey].')  |  ' . $th->getMessage();
                    continue;                    
                }
                \DB::commit();
                $personalCreated[] = $personal->id;
                $this->newUsers[] = $personal;
            }
        }
        $personalDeleted = 0;
        $personalNotToDelete = array_merge($personalUpdated, $personalCreated);
        if(count($personalNotToDelete) > 0){
            try {
                $queryPersonals = User::whereNotIn('id', $personalNotToDelete);
                $personalDeleted = $queryPersonals->count();
                $queryPersonals->delete();
            } catch (\Throwable $th) {

            }            
        }
        dd($this->dataError, $this->update_errors, $this->create_errors, count($personalUpdated), $personalDeleted);
        */
    }

    /**
     * Formateamos la información del empleado para informar sobre quien se trabajo
     *
     * @param  array $data
     * @param  string $error
     *
     * @return void
     */
    public function formatMessageWithPersonnelData($data, $error = "", $specific = ""){
        $msg = "";
        if(!empty($error)){
            $msg = $this->formatErrorMessage($data, $error, $specific) . ' || ';
        }
        $aux = trim($data['nombre'] . ' ' . $data['paterno'] . ' ' . $data['materno']);
        $msg .= (!empty($aux))?'Nombre: ' . $aux . ' -- ' : '';
        $aux = trim($data['rfc']);
        $msg .= (!empty($aux))?'RFC: ' . $aux . ' -- ' : '';
        $aux = trim($data['correoempresa']);
        $msg .= (!empty($aux))?'CORREOEMPRESA: ' . $aux . ' -- ' : '';
        $aux = trim($data['correopersonal']);
        $msg .= (!empty($aux))?'CORREOPERSONAL: ' . $aux . ' -- ' : '';
        $msg = trim($msg);
        $msg = rtrim($msg, ' --');
        return $msg;
    }

    /**
     * A partir de los parametros formeatea el error solicitado
     *
     * @param  array $data
     * @param  string $error
     * @param  string $specific
     *
     * @return string
     */
    public function formatErrorMessage($data, $error, $specific = ""){
        $msg = "";
        switch($error){
            case "FUENTE":
                $msg = 'Se quiere asignar la fuente(' . $user['fuente'] . 
                ') que no corresponde a la primera(' . $this->firstDetectedSource . ')';
            break;
            case "DUPLICATE_FIELDS":
                $msg = 'Conflicto de duplicidad con alguno de los siguientes campos (';
                $msg .= implode(', ', $this->uniquePersonalSimulatedFields);
                $msg .= ')';
            break;
            case "BALANCE_CREATE":
                $msg = "Error al intentar crear la linea de balance de vacaciones" .
                " ( " . $specific . " )"; 
            break;
            case "BALANCE_UPDATE":
                $msg = "Error al intentar actualizar la linea de balance de vacaciones" .
                " ( " . $specific . " )"; 
            break;
            case "EXCEPTION":
                $msg = 'Excepción encontrada (' . $specific . ')';
        }
        return $msg;
    }

    /**
     * Aqui estructuramos las consultas necesarias para verificar 
     * si no hay complictos con campos unicos en la base de datos
     *
     * @param  array $userData
     * @param  array $personalData
     *
     * @return bool
     */
    public function verfyUniqueFields($userData, $personalData = null){
        /*$unique = false;
        if($this->usesEmployeeModel){
            $personal = People::when($personalData, function($q) use($personalData){
                $q->where('id', '!=', $personalData->id);
            })
            ->where(function($q) use($userData){
                $this->whereClausesForUnique($q, $userData, 'employee');
            })
            ->first();
            
            $personalAux = null;
            if($personalData){
                $personalAux = Employee::with('user')->find($personalData->id);
            }

            $user = null;
            $user = User::when($personalAux && $personalAux->user, function($q) use ($personalAux){
                $q->where('id', '!=', $personalAux->user->id);
            })
            ->where(function($q) use ($userData){
                $this->whereClausesForUnique($q, $userData);
            })
            ->first();

            $unique = !$personal && !$user;
        }else{
            $user = User::when(!is_null($personalData), function($q) use($personalData){
                $q->where('id', '!=', $personalData->id);
            })
            ->where(function($q) use ($userData){
                $this->whereClausesForUnique($q, $userData);
            })
            ->count();
            $unique = ($user > 0)?false:true;
        }
        return $unique;*/
    }

    /**
     * Intenta reactivar un usuario borrado logicamente si es necesario
     *
     * @param  Illuminate\Database\Eloquent\Model $personal
     * @param  bool $first
     *
     * @return bool
     */
    public function activatedIfNeeded($personal, $first = true){
        $val = false;
        if($personal){
            if(!is_null($personal->deleted_at)){
                try {
                    $personal->restore();
                    $val = true;
                } catch (\Throwable $th) {
                    return false;
                }
            }
            $val = true;
        }
        if($this->usesEmployeeModel && $first){
            return $val && $this->activatedIfNeeded($personal->user, false);
        }
        return $val;
    }

    /**
     * Actualiza la información del modelo con la información proporcionada
     * ignorado los elementos listados en @var $this->except_model_fields
     *
     * @param  Illuminate\Database\Eloquent\Model $personal
     * @param  array $data
     *
     * @return void
     */
    public function changePersonalData($personal, $data){
        foreach ($data as $key => $value) {
            if(!in_array($key, $this->except_model_fields))
                $personal->$key = $value;
        }
        return $personal;
    }
}
