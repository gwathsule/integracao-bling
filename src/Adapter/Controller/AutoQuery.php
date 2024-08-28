<?php

namespace Src\Adapter\Controller;

class AutoQuery {

    var $mSql;
    var $mResult;
    var $mRows;
    var $mCursor;
    var $mFetchAssoc = false;
    var $mUTF8Encode = false;
    var $mUTF8Decode = false;
    var $mArrField = [];
    var $mArrFieldValue = [];
    var $mArrDeclare = [];
    var $mWhere = '';
    var $mDebug = false;
    var $mDebugEcho = false;
    var $mDebugTimeStart = 0;
    var $mDebugEchoTime = false;
    var $mDebugContent = '';
    var $mError = '';
    var $mAuditoria = null;
    var $mQueryOldValues = null;

    var $mSetIdentityInsert = false;

    var $mSqlPrevious = '';
    var $mSqlAppend = '';
    var $mSqlDeclare = '';

    var $mFetchAfterExecute = false;

    var $mCampo = '';
    var $mValor = '';
    var $mSet = '';
    var $mPk = '';
    var $mPkZero = false;
    var $mTemIdentity = false;

    var $mRollbackOnError = false;

    function __construct($pSql='') {
        if ($pSql!='') {$this->mSql=$pSql;}
        $this->mUTF8Encode = "utf8_encode"; /* por padrão usar a opção definida na constante */
        $this->mUTF8Decode = "utf8_decode";
    }

    function debug($pEnable) {
        if (gettype($pEnable)!='boolean') {
            $pEnable = true;
        }
        $this->mDebug = ($pEnable && (getSession('cdUsuario')==1 || (defined('USER_DEV') && USER_DEV) || getSession('logadoComo')==1 || getRequest("acao")=="logarComo"));
    }

    function debugEcho($pEnable) {
        if (gettype($pEnable)!='boolean') {
            $pEnable = true;
        }
        $this->mDebugEcho = ($pEnable && (getSession('cdUsuario')==1 || (defined('USER_DEV') && USER_DEV) || getSession('logadoComo')==1 || getRequest("acao")=="logarComo"));
    }

    function debugEchoTime($pEnable) {
        if (gettype($pEnable)!='boolean') {
            $pEnable = true;
        }
        $this->mDebugTimeStart = microtime(true);
        $this->mDebugEchoTime = ($pEnable && (getSession('cdUsuario')==1 || (defined('USER_DEV') && USER_DEV) || getSession('logadoComo')==1 || getRequest("acao")=="logarComo"));
    }

    function fetchAssoc() {
        $this->mFetchAssoc = true;
    }
    function fetchBoth() {
        $this->mFetchAssoc = false;
    }

    function exec($pSql='', $pReturn=false) {
        global $db;
        if ($pSql!='') {
            $this->mSql = $pSql;
        }

        if ($this->mSqlDeclare!='') {
            $this->mSql = $this->mSqlDeclare. "/* VARIAVEIS DECLARADAS */".PHP_EOL. $this->mSql;
        }

        foreach ($this->mArrFieldValue as $key1 => $fieldValue) {
            //debugAdd('key1='.$key1.' fieldValue='.$fieldValue);
            $arrBind = [];
            if (strpos($key1,'[n]')!==false) {
                $arrFieldValue = explode(',', $fieldValue);
                foreach ($arrFieldValue as $key2 => $value) {
                    //debugAdd('key2='.$key2.' value='.$value);
                    $nameBind = str_replace('[n]','',$key1).$key2;
                    $this->mArrFieldValue[$nameBind] = $value;
                    $arrBind[] = $nameBind;
                    //$this->mCampo[$this->mIndex]['value'] = [$pCampo, $pRequest, $pTipo, $pOffset];
                }
                unset($this->mArrFieldValue[$key1]);
                $newBind = implode(", ", $arrBind);
                $this->mSql = str_replace($key1, $newBind, $this->mSql);
            }
        }
        $this->debugSql($this->mSql);

        if ($pReturn) {

            return $this->mSql;

        } else {

            $this->mResult = $this->mSql==null ? [] : $db->prepare($this->mSql);

            if (count($this->mArrFieldValue)>0) {
                $this->mResult->execute($this->mArrFieldValue);
            } else {
                $this->mResult->execute();
            }

            $this->debugSqlTime();

            $this->debugSql('rowCount='.$this->mResult->rowCount());

            //$this->mResult = $db->prepare($this->mSql);
            //$this->mResult->execute();
            //$rowsAffected = $this->mResult->rowCount();

            /*
            $affected = $this->mResult = $db->exec($this->mSql);
            $this->debugSql('rowsAffected='.$affected);
            */

            //if ($affected === false) {
            $dbErro = $db->errorInfo();
            $this->debugSql('dbErro='.print_r($dbErro, true));

            //}

            $this->mRows = [];
            $this->mCursor = 0;
        }
    }

    function open($pSql='') {
        global $db;
        if ($pSql!='') {
            $this->mSql = $pSql;
        }

        if ($this->mSqlDeclare!='') {
            $this->mSql = $this->mSqlDeclare. "/* VARIAVEIS DECLARADAS */".PHP_EOL. $this->mSql;
        }

        if ($this->mSql!='') {
            foreach ($this->mArrFieldValue as $key1 => $fieldValue) {
                //debugAdd('key1='.$key1.' fieldValue='.$fieldValue);
                $arrBind = [];
                if (strpos($key1,'[n]')!==false) {
                    $arrFieldValue = explode(',', $fieldValue);
                    foreach ($arrFieldValue as $key2 => $value) {
                        //debugAdd('key2='.$key2.' value='.$value);
                        $nameBind = str_replace('[n]','',$key1).$key2;
                        $this->mArrFieldValue[$nameBind] = $value;
                        $arrBind[] = $nameBind;
                        //$this->mCampo[$this->mIndex]['value'] = [$pCampo, $pRequest, $pTipo, $pOffset];
                    }
                    unset($this->mArrFieldValue[$key1]);
                    $newBind = implode(", ", $arrBind);
                    $this->mSql = str_replace($key1, $newBind, $this->mSql);
                }
            }
            $this->debugSql($this->mSql);

            /* TESTANDO PREPARE */
            $this->mResult = $db->prepare($this->mSql);

            if (count($this->mArrFieldValue)>0) {
                //$this->mResult->bindParam(':dsFilial', '%a%', PDO::PARAM_STR);
                $this->mResult->execute($this->mArrFieldValue);
            } else {
                //try {
                $this->mResult->execute();
                //}
                //catch(PDOException $exception){
                //	echo $exception;
                //}

            }

            $this->debugSqlTime();

            if ($this->mResult) {
                if (strpos($this->mSql,'CREATE TABLE #')!==false) $this->mResult->nextRowset();
                //debugAdd(print_r($this->mResult,true));
                if ($this->mFetchAssoc) {
                    $this->mRows = $this->mResult->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $this->mRows = $this->mResult->fetchAll();
                }
            } else {
                $this->mRows = [];
            }
        } else {
            $this->mRows = null;
        }
        $this->mCursor = 0;
        //$this->mArrFieldValue = [];
        //echo 'count='.count($this->mRows).PHP_EOL;
        //print_r($this->mRows);
    }

    function openTest() {
        global $db;
        $this->debugSql($this->mSql);

        /* PDO */
        $this->mResult = $db->query($this->mSql);

        //$this->mResult = $mysqli->query($this->mSql);
        /*
        if ($this->mResult) {
            //debugAdd(print_r($this->mResult,true));
            $this->mRows = $this->mResult->fetchAll();
        } else {
            $this->mRows = [];
        }
        */
        $this->mCursor = 0;

        //echo 'count='.count($this->mRows).PHP_EOL;
        //print_r($this->mRows);
    }

    function rowCount() {
        return (count($this->mRows));
    }
    function colCount() {
        return (count($this->mRows[0])/2);
    }

    function rowNumber() {
        return $this->mCursor+1;
    }

    function cursorPos() {
        return $this->mCursor;
    }

    function eof() {
        if ($this->mRows===null) {
            $this->open();
        }
        //echo $this->mCursor.'='.count($this->mRows).PHP_EOL;
        return ($this->mCursor>=count($this->mRows) || count($this->mRows)==0);
    }
    function notEof() {
        if ($this->mRows===null) {
            $this->open();
        }
        return ($this->mCursor<count($this->mRows));
    }
    function next() {
        $this->mCursor++;
        //echo 'mCursor='.$this->mCursor;
        return ($this->mCursor<count($this->mRows));
    }
    function prior() {
        $this->mCursor--;
        return ($this->mCursor>=0);
    }
    function go($pCursor) {
        $this->mCursor = $pCursor;
    }
    function first() {
        $this->mCursor = 0;
    }

    var $arrSqlTypes = [
        'int' => NUMBER,
        'decimal' => NUMBER,
        'varchar' => STRING,
        'date' => DATE,
        'datetime' => DATETIME,
        'smalldatetime' => DATETIME
    ];

    function fieldType($pField) {
        if (is_numeric($pField)) {
            $column = $pField;
        } else {
            //localizar a coluna
            $column = $pField;
        }
        $info = $this->mResult->getColumnMeta($column);
        $type = getArrayValue('sqlsrv:decl_type', $info);
        return $type;



        /*
        Array
        (
            [flags] => 0
            [sqlsrv:decl_type] => decimal
            [native_type] => string
            [table] =>
            [pdo_type] => 2
            [name] => qtDecimal
            [len] => 15
            [precision] => 2
        )
        */
    }

    function field($pField, $pDefault=null, $pCursor=null) {
        if ($this->mRows===null) {
            $this->open();
        }
        if (!isset($pCursor)) { $pCursor = $this->mCursor; }
        //echo 'pField='.$pField;
        try {
            $value = ( $this->mRows===null || !array_key_exists($pCursor,$this->mRows) ? null : $this->mRows[$pCursor][$pField] );
        }
        catch(Exception $e) {
            //echo 'Message: ' .$e->getMessage();
            echo 'Backtrace: '.print_r(debug_backtrace()[1]['line'],true);
        }

        if ($pDefault!==null && $value==null) { $value = $pDefault; }
        if ($this->mUTF8Encode && !is_null($value)) { $value = utf8_encode($value); }
        return $value;
    }
    function trimField($pField, $pDefault=null, $pCursor=null) {
        $field = $this->field($pField, $pDefault, $pCursor);
        return trim($field==null ? '' : $field);
    }
    function intField($pField, $pDefault=null, $pCursor=null) {
        return intval($this->field($pField, $pDefault, $pCursor));
    }
    function floatField($pField, $pDefault=null, $pCursor=null) {
        return floatval($this->field($pField, $pDefault, $pCursor));
    }

    /**
     * Prepara a consulta de verificação da auditoria
     * @param object $pObj Objeto de principal da AutoAuditoria
     * @param string $pSelect Select de busca incluindo o where de os binds
     * @param mixed $pControle Bind de busca, string para controle único ou array
     */
    function auditoria( &$pObj, $pSelect, $pControle='') {
        $this->mAuditoria = $pObj;
        $this->mQueryOldValues = new AutoQuery($pSelect);
        if (gettype($pControle)=='array' && $pControle!=[]) {
            $this->mQueryOldValues->value($pControle);
        } else
            if ($pControle!='') {
                $this->mQueryOldValues->value(':controle', $pControle);
            }
    }

    function setValueAudit($pField, $pValue, $pTipo=NUMBER, $pNomeAudit=null, $pSenha=false) {
        $this->setValue($pField, $pValue, $pTipo, null, null, $pNomeAudit, $pSenha);
    }

    function setValueNotNull($pField, $pValue, $pTipo=NUMBER, $pNomeAudit=null, $pSenha=false) {
        if ($pValue!=null) {
            $this->setValue($pField, $pValue, $pTipo, null, null, $pNomeAudit, $pSenha);
        }
    }

    function setNoCount() {
        $this->mSqlPrevious = "set nocount on; ";
    }

    function declare($pSqlVariable, $pValue, $pType, $pSqlType='') {

        if ($pSqlType=='') {
            if ($pType===NUMBER) {
                $pSqlType = 'numeric(20,10)';
            } else {
                $pSqlType = 'varchar(max)';
            }
        }

        $this->mArrDeclare[$pSqlVariable]['sqlType'] = $pSqlType;
        $this->mArrDeclare[$pSqlVariable]['value'] = $pValue;
        $this->mArrDeclare[$pSqlVariable]['tipo'] = $pType;

        $bind = str_replace('@',':',$pSqlVariable);
        $this->value($bind,$pValue,$pType);

        $this->mSqlDeclare.= "DECLARE $pSqlVariable $pSqlType = $bind; ".PHP_EOL;
    }

    /**
     * Descrição
     * @param string $pField Nome do campo
     * @param mixed $pValue Valora ser gravado
     * @param integer $pTipo Tipo do campo NUMBER, STRING, DATE, DATETIME, CURRENCY
     * @param boolean $pPk
     * @param boolean $pIdentity
     * @return string Retorna o ano
     */
    function setValue($pField, $pValue, $pTipo=NUMBER, $pPk=null, $pIdentity=null, $pNomeAudit=null, $pSenha=false) {
        if ($this->mUTF8Decode && !is_null($pValue)) { $pValue = utf8_decode($pValue); }
        if ($pValue=='NaN') $pValue = '0';

        if (substr($pField, 0, 1)=='@') {
            if ($pTipo==DATE && $pValue=='') {
                $pField = str_replace('@',':',$pField);
            } else {
                $this->declare($pField, $pValue, $pTipo);
                $pValue = $pField;
                $pTipo = null;
                $pField = str_replace('@','',$pField);
            }
        }

        if (substr($pField, 0, 1)==':' && $pTipo==DATE) {
            if ($pValue=='') {
                $pField = substr($pField,1);
                $this->mArrField[$pField][0] = 'null';
            } else {
                $this->mArrField[$pField][0] = gravaData($pValue);
            }
        } else
            if (substr($pField, 0, 1)==':' && $pTipo==DATETIME) {
                if ($pValue=='') {
                    $pField = substr($pField,1);
                    $this->mArrField[$pField][0] = 'null';
                } else {
                    $this->mArrField[$pField][0] = gravaDataHora($pValue);
                }
            } else
                if (substr($pField, 0, 1)==':' && ($pTipo==STRING || $pTipo==STRING_NOUPPER)) {
                    //TODO: verificar o tipo e adaptar para evitar adicionar aspas dentro no valor
                    if ($pTipo===STRING && ID_MAIUSCULA) { $pValue = mb_strtoupper($pValue, 'UTF-8'); }
                    $this->mArrField[$pField][0] = $pValue;
                } else {
                    $this->mArrField[$pField][0] = verify($pValue,$pTipo);
                }
        $this->mArrField[$pField][1] = $pPk;
        $this->mArrField[$pField][2] = $pIdentity;

        if ($pNomeAudit!=null) {

            $pValueOld = trim( $this->mQueryOldValues->field(str_replace(':','',$pField)) ?? '' );
            $pValueNew = str_replace("'","",verify(trim($pValue), $pTipo));

            if ($pTipo==NUMBER) {

                $pValueNew = str_replace('.',',',$pValueNew);
                $arrNew = explode(',', $pValueNew);
                if ($arrNew[0]=='') $arrNew[0] = '0';
                if (count($arrNew)>1) {
                    $arrNew[1] = trim(','.$arrNew[1], '0');
                    $arrNew[1] = substr($arrNew[1],1);
                    if ($arrNew[1]=='') {
                        unset($arrNew[1]);
                    }
                }
                $pValueNew = implode(',', $arrNew);

                $pValueOld = str_replace('.',',',$pValueOld);
                $arrOld = explode(',', $pValueOld);
                if ($arrOld[0]=='') $arrOld[0] = '0';
                if (count($arrOld)>1) {
                    $arrOld[1] = trim(','.$arrOld[1], '0');
                    $arrOld[1] = substr($arrOld[1],1);
                    if ($arrOld[1]=='') {
                        unset($arrOld[1]);
                    }

                }
                $pValueOld = implode(',', $arrOld);

                /* TESTE
                if ($pField==':vlLimiteCredito') {
                    echoPre('pValueOld:', $pValueOld, 'pValueNew:', $pValueNew);
                    exit;
                }
                */

            }

            if ($pTipo==DATE) {
                $pValueOld = ($pValueOld=='null'?'':exibeDataValida($pValueOld));
                $pValueNew = ($pValueNew=='null'?'':exibeDataValida($pValueNew));
            } else
                if ($pTipo==DATETIME) {
                    $pValueOld = ($pValueOld=='null'?'':exibeDataHoraValida($pValueOld));
                    $pValueNew = ($pValueNew=='null'?'':exibeDataHoraValida($pValueNew));
                } else
                    if ($pValueNew=='getdate()') {
                        $pValueOld = ($pValueOld=='null'?'':exibeData($pValueOld));
                        $pValueNew = exibeData();
                    } else
                        if ($pValueNew=='null') {
                            $pValueOld = $pValueOld;
                            $pValueNew = '';
                        }

            $this->mAuditoria->item($pNomeAudit, $pValueOld, $pValueNew, $pSenha);
        }

    }

    function setWhere($pField, $pValue, $pTipo=NUMBER) {
        $this->setValue($pField, $pValue, $pTipo, true, true);
    }
    function whereValue($pField, $pValue, $pTipo=NUMBER) {
        $this->setValue($pField, $pValue, $pTipo, true, true);
    }

    function value($pField, $pValue='', $pTipo=NUMBER) {
        if (gettype($pField)=='array') {
            $this->mArrFieldValue = $pField;
        } else {
            if (substr($pField, 0, 1)==':' && $pTipo==DATE) {
                if ($pValue=='') {
                    $pField = substr($pField,1);
                    $this->mArrField[$pField][0] = 'null';
                    $pValue = 'null';
                } else {
                    //$this->mArrField[$pField][0] = gravaData($pValue);
                    $pValue = gravaData($pValue);
                }
                $pTipo = null;
            } else
                if (substr($pField, 0, 1)==':' && ($pTipo==STRING || $pTipo==STRING_NOUPPER)) {
                    //TODO: verificar o tipo e adaptar para evitar adicionar aspas dentro no valor
                    if ($pTipo===STRING && ID_MAIUSCULA) { $pValue = mb_strtoupper($pValue??'', 'UTF-8'); }
                    //$this->mArrField[$pField][0] = $pValue;
                    $pTipo = null;
                } else
                    if ($this->mUTF8Decode && !is_null($pValue)) {
                        $pValue = utf8_decode($pValue);
                    }
            $this->mArrFieldValue[$pField] = verify($pValue,$pTipo);
        }
    }

    function where($pWhere) {
        $this->mWhere = $pWhere;
    }

    function inTransaction() {
        global $db;
        return $db->inTransaction();
    }

    function beginTran() {
        global $db;
        $db->beginTransaction();
    }
    function beginTransaction() {
        global $db;
        $db->beginTransaction();
    }

    function rollback() {
        global $db;
        $db->rollBack();
    }

    function commit() {
        global $db;
        $db->commit();
    }
    function commitTran() {
        global $db;
        $db->commit();
    }

    function debugSql($pSql) {
        $fieldValue = print_r($this->mArrFieldValue, true);
        $fieldValue = trim(str_ireplace('Array', '', $fieldValue));
        $fieldValue = trim(preg_replace('/\(/', '', $fieldValue, 1));
        $fieldValue = trim(preg_replace('/\)$/', '', $fieldValue, 1));
        $fieldValue = trim(preg_replace('/\n    /', PHP_EOL, $fieldValue));
        if ($this->mDebug) {
            $this->mDebugContent.= $pSql.PHP_EOL;
            debugAdd($pSql);
            if ($fieldValue!='') {
                debugAdd($fieldValue);
                $this->mDebugContent.= $fieldValue.PHP_EOL;
            }
        } else {
            $pSql = $pSql==null ? '' : str_replace(chr(9), ' ', $pSql);
            $declare = '';
            foreach ($this->mArrFieldValue as $key => $value) {
                if (substr($key,0,1)==':') {
                    $key = substr($key,1);
                    $pSql = str_replace(':'.$key, '@_'.$key, $pSql);
                }
                $declare.="DECLARE @_$key varchar(4000) = '$value';".PHP_EOL;
            }
            if ($declare!='') $declare.="/*****/".PHP_EOL;

            $this->mDebugContent.= $declare.$pSql.PHP_EOL;

            if ($this->mDebugEcho || $this->mDebugEchoTime) {
                //echoPre($pSql. ($fieldValue=='' ? '' : PHP_EOL.$fieldValue ) );
                echoPre($declare.$pSql);
            }
        }
        /* else
        if ($this->mDebug==='show') {
            echo $pSql;
        }
        */
    }

    function debugSqlTime() {
        if ($this->mDebugEchoTime) {

            $tempo1 = (microtime(true) - $this->mDebugTimeStart);
            $tempo2 = (microtime(true) - DEBUG_TIME_START);

            echoPre(
                'Tempo de execução:', $tempo1,
                'Tempo total até aqui:', $tempo2
            );
        }

    }

    function getDebug() {
        return $this->mDebugContent;
    }

    /**
     * Prepara os campo para update ou insert
     */
    function preparFields($pOption='',$pPrimaryKeysOnly=false) {
        $this->mCampo = '';
        $this->mValor = '';
        $this->mSet = '';
        $this->mPk = '';
        $this->mPkZero = false;
        $this->mTemIdentity = false;

        //if ($pOption=='insert') {
        //	unset($this->mArrField[':cdModelo']);
        //}

        foreach($this->mArrField as $key=>$value) {
            //while (list($key, $value) = each($this->mArrField)) {

            $val = $value[0];
            $bind = (substr($key,0,1)==':');
            $sqlVar = (substr($key,0,1)=='@');
            $iden = ($value[2]==true);
            if (!$pPrimaryKeysOnly || ($pPrimaryKeysOnly && $value[1])) {
                if ($bind && ( $pOption=='' || ($pOption=='select' && $value[1]) || ($pOption=='update' && $value[1]) || ($pOption=='insert') ) ) {
                    $key = substr($key,1);
                    $val = ':'.$key;
                    $this->mArrFieldValue[$val] = $value[0];
                }
                if ($value[2]!==true) { // se o campo não for identity
                    $this->mSet.= $key."=".$val.", ";
                    $this->mCampo.= $key.", ";
                    $this->mValor.= $val.", ";
                } else if ($value[2]==true) { // se for identity marcar que tem
                    $this->mTemIdentity = true;
                }
            }
            if ($value[1]==true) {  // se for chave primaria, incluir no where
                if ($bind && $pOption!='insert' && !$iden) {
                    $val.='_pk';
                    $this->mArrFieldValue[$val] = $value[0];
                }
                $this->mPk.= $key."=".$val." and ";
                $this->mPkZero = (vazioOuZero($val));
            }
        }
        $this->mSet = substr($this->mSet, 0, -2);
        $this->mCampo = substr($this->mCampo, 0, -2);
        $this->mValor = substr($this->mValor, 0, -2);
        $this->mPk = substr($this->mPk, 0, -5);
        if ($this->mPk!="") {
            $this->mPk = "(".$this->mPk.")";
            if ($this->mWhere!=="") {
                $this->mPk.= " and ";
            }
        }
    }

    function setIdentityInsert($pBoolean=true) {
        $this->mSetIdentityInsert = $pBoolean;
    }

    function insertInto($pTable='', $pReturn=false, $asSelectUnion=false, $getValuesOnly=false) {
        global $db;
        $this->preparFields();

        $sql = "";

        if ($this->mSqlDeclare!='') {
            $sql = $this->mSqlDeclare. "/* VARIAVEIS DECLARADAS */".PHP_EOL. $sql;
        }

        $sql.= $this->mSqlPrevious." ";
        $this->mSqlPrevious = "";

        if ($asSelectUnion) {

            $sql.= "select ".$this->mValor." union ".PHP_EOL;

        } else
            if ($getValuesOnly) {

                $sql.= "(".$this->mValor.")";
                $pReturn = true;

            } else {

                if ($this->mSetIdentityInsert) {
                    $sql.= "set identity_insert ".$pTable." on; ";
                }

                $sql.= "insert into ".$pTable." (".$this->mCampo.") values (".$this->mValor."); ";

                if ($this->mSetIdentityInsert) {
                    $sql.= "set identity_insert ".$pTable." off; ";
                }

            }

        $sql.= " ".$this->mSqlAppend." ";
        $this->mSqlAppend = "";

        $this->debugSql($sql);

        $this->mSql = $sql;

        if ($pReturn) {

            return $this->mSql;

        } else {

            //var_dump($sql);

            if (strpos($sql, ':')!=false && count($this->mArrFieldValue)>0) {
                $this->mResult = $db->prepare($this->mSql);
                $this->mResult->execute($this->mArrFieldValue);

                if ($this->mFetchAfterExecute) {
                    if ($this->mResult) {
                        //debugAdd(print_r($this->mResult,true));
                        if ($this->mFetchAssoc) {
                            $this->mRows = $this->mResult->fetchAll(PDO::FETCH_ASSOC);
                        } else {
                            $this->mRows = $this->mResult->fetchAll();
                        }
                    } else {
                        $this->mRows = [];
                    }
                }


            } else {
                $this->mArrField = [];
                $this->mResult = $db->query($this->mSql);
            }

            $this->debugSqlTime();

        }

    }

    function update($pTable, $pReturn=false) {
        global $db;

        /*
        foreach ($this->mArrFieldValue as $key1 => $fieldValue) {
            //debugAdd('key1='.$key1.' fieldValue='.$fieldValue);
            $arrBind = [];
            if (strpos($key1,'[n]')!==false) {
                $arrFieldValue = explode(',', $fieldValue);
                foreach ($arrFieldValue as $key2 => $value) {
                    //debugAdd('key2='.$key2.' value='.$value);
                    $nameBind = str_replace('[n]','',$key1).$key2;
                    $this->mArrFieldValue[$nameBind] = $value;
                    $arrBind[] = $nameBind;
                    //$this->mCampo[$this->mIndex]['value'] = [$pCampo, $pRequest, $pTipo, $pOffset];
                }
                unset($this->mArrFieldValue[$key1]);
                $newBind = implode(", ", $arrBind);
                $this->mSql = str_replace($key1, $newBind, $this->mSql);
            }
        }
        */

        $this->preparFields();

        $sql = "";

        if ($this->mSqlDeclare!='') {
            $sql = $this->mSqlDeclare. "/* VARIAVEIS DECLARADAS */".PHP_EOL. $sql;
        }

        $sql.= $this->mSqlPrevious." ";
        $this->mSqlPrevious = "";

        $sql.= "update ".$pTable." set ".$this->mSet.($this->mPk.$this->mWhere!='' ? " where ".$this->mPk.$this->mWhere."; " : "; ");

        $this->debugSql($sql);

        $this->mSql = $sql;

        if ($pReturn) {

            reset($this->mArrField);
            return $this->mSql;

        } else {

            if ($this->mSet=='') {
                return false;
            }

            if (strpos($sql, ':')!=false && count($this->mArrFieldValue)>0) {
                $this->mResult = $db->prepare($this->mSql);

                $this->mResult->execute($this->mArrFieldValue);

                $this->debugSqlTime();

            } else {
                $this->mArrField = [];
                $this->mResult = $db->query($this->mSql);

                $this->debugSqlTime();

                return $this->mResult->rowCount();
            }

        }


        //$this->open(); //$db->query($sql);
        //echo $sql;
        //exit;
    }

    function updateSelect($pTable, $pPrimaryField, $pReturn=false) {
        global $db;

        $this->preparFields('',true);
        $where = $this->mPk.$this->mWhere;

        $this->mSql = "SELECT $pPrimaryField from $pTable where $where";
        $this->open();

        foreach ($this->mRows as $key => $value) {

            $this->preparFields();

            $valuePri = $value[$pPrimaryField];

            $wherePri = ($this->mPk.$this->mWhere!='' ? " and" : "")." $pPrimaryField=$valuePri";

            $sql = "update ".$pTable." set ".$this->mSet.($this->mPk.$this->mWhere.$wherePri!='' ? " where ".$this->mPk.$this->mWhere.$wherePri."; " : "; ");
            $this->debugSql($sql);

            $this->mSql = $sql;

            if ($pReturn) {

                reset($this->mArrField);
                return $this->mSql;

            } else {

                if ($this->mSet=='') {
                    return false;
                }

                if (strpos($sql, ':')!=false && count($this->mArrFieldValue)>0) {
                    $this->mResult = $db->prepare($this->mSql);

                    $this->mResult->execute($this->mArrFieldValue);

                    $this->debugSqlTime();

                } else {
                    $this->mArrField = [];
                    $this->mResult = $db->query($this->mSql);

                    $this->debugSqlTime();

                    return $this->mResult->rowCount();
                }

            }

        }//foreach

        //$this->open(); //$db->query($sql);
        //echo $sql;
        //exit;
    }

    function insertSelect($pTableInsert, $pTableSelect, $pReturn=false) {
        global $db;
        $this->preparFields();

        $sql = "";

        $sql.= $this->mSqlPrevious." ";
        $this->mSqlPrevious = "";

        $sql.= "insert into ".$pTableInsert." (".$this->mCampo.") select ".$this->mValor." from ".$pTableSelect." where ".$this->mPk.$this->mWhere."; ";
        $this->debugSql($sql);

        $this->mSql = $sql;

        if ($pReturn) {

            return $this->mSql;

        } else {

            //var_dump($sql);

            if (strpos($sql, ':')!=false && count($this->mArrFieldValue)>0) {
                $this->mResult = $db->prepare($this->mSql);
                if ($this->mDebug) {
                    //debugAdd(print_r($this->mArrFieldValue, true));
                }
                $this->mResult->execute($this->mArrFieldValue);

            } else {
                $this->mArrField = [];
                $this->mResult = $db->query($this->mSql);

            }

            $this->debugSqlTime();

        }

    }

    function insertSelectDistinct($pTableInsert, $pTableSelect, $pReturn=false) {
        global $db;
        $this->preparFields();

        $sql = "";

        $sql.= $this->mSqlPrevious." ";
        $this->mSqlPrevious = "";

        $sql.= "insert into ".$pTableInsert." (".$this->mCampo.") select distinct ".$this->mValor." from ".$pTableSelect." where ".$this->mPk.$this->mWhere."; ";
        $this->debugSql($sql);

        $this->mSql = $sql;

        if ($pReturn) {

            return $this->mSql;

        } else {

            //var_dump($sql);

            if (strpos($sql, ':')!=false && count($this->mArrFieldValue)>0) {
                $this->mResult = $db->prepare($this->mSql);
                if ($this->mDebug) {
                    //debugAdd(print_r($this->mArrFieldValue, true));
                }
                $this->mResult->execute($this->mArrFieldValue);

            } else {
                $this->mArrField = [];
                $this->mResult = $db->query($this->mSql);

            }

            $this->debugSqlTime();

        }

    }

    function insertUpdate($pTable, $pOption="") {
        global $db;
        //print_r($this->mArrField);

        $campo = '';
        $valor = '';
        $set = '';
        $pk = '';
        $pkZero = false;
        $temIdentity = false;

        //while (list($key, $value) = each($this->mArrField)) {
        foreach ($this->mArrField as $key => $value) {
            if ($value[2]!==true) { // se o campo não for identity
                $set.= $key."=".$value[0].", ";
                $campo.= $key.", ";
                $valor.= $value[0].", ";
            } else if ($value[2]==true) { // se for identity marcar que tem
                $temIdentity = true;
            }
            if ($value[1]==true) { // se for chave primaria, incluir no where
                $pk.= $key."=".$value[0]." and ";
                $pkZero = (vazioOuZero($value[0]));
            }
        }
        $set = substr($set, 0, -2);
        $campo = substr($campo, 0, -2);
        $valor = substr($valor, 0, -2);
        $pk = substr($pk, 0, -5);
        if ($pk!=="") {
            $pk = "(".$pk.")";
            if ($this->mWhere!=="") {
                $pk.= " and ";
            }
        }

        $sql = "begin tran ";

        $sql.= $this->mSqlPrevious." ";
        $this->mSqlPrevious = "";

        if (!$pkZero || $this->mWhere!=='') {
            $sql.=   "update ".$pTable." set ".$set." where ".$pk.$this->mWhere."; ";
            $sql.=   "if @@rowcount = 0 begin ";
        }

        $sql.=     "insert into ".$pTable." (".$campo.') values ('.$valor.'); ';

        if (!$pkZero || $this->mWhere!=='') {
            $sql.=   "end ";
        }
        $sql.= "commit tran ";

        $this->debugSql($sql);

        $this->mSql = $sql;

        $this->mArrField = [];

        if (strpos($sql, ':')!=false && count($this->mArrFieldValue)>0) {
            $this->mResult = $db->prepare($this->mSql);
            if ($this->mDebug) {
                //debugAdd(print_r($this->mArrFieldValue, true));
            }
            $this->mResult->execute($this->mArrFieldValue);

        } else {
            $this->mArrField = [];
            $this->mResult = $db->query($this->mSql);

        }

        $this->debugSqlTime();



        //try{
        //$this->mResult = $db->query($this->mSql);
        //}
        //catch(PDOException $exception){
        //	$this->mError = $exception;
        //}
        //
        //$this->open(); //$db->query($sql);
        //echo $sql;
        //exit;
    }

    function insertUpdate2($pTable, $pOption="") {
        global $db;

        $this->preparFields();

        $rowsAffected = 0;

        if (!$this->mPkZero || $this->mWhere!=='') {
            $sql = "update ".$pTable." set ".$this->mSet." where ".$this->mPk.$this->mWhere."; ";
            $this->debugSql($sql);

            $this->mSql = $sql;
            if (strpos($sql, ':')!=false && count($this->mArrFieldValue)>0) {
                $this->mResult = $db->prepare($this->mSql);

                $this->mResult->execute($this->mArrFieldValue);
            } else {
                $this->mResult = $db->query($this->mSql);
            }

            $this->debugSqlTime();

            $rowsAffected = $this->mResult->rowCount();
        }
        //echo 'rowsAffected='.$rowsAffected;

        if ($rowsAffected==0) {

            foreach ($this->mArrField as $key => $value) {
                //echo $value;
                if ($value[2]) {
                    unset($this->mArrField[$key]);
                }
            }

            $this->mArrFieldValue = [];
            $this->preparFields('insert');

            $sql = "insert into ".$pTable." (".$this->mCampo.') values ('.$this->mValor.'); ';
            $this->debugSql($sql);

            $this->mSql = $sql;

            $this->mArrField = [];

            if (strpos($sql, ':')!=false && count($this->mArrFieldValue)>0) {
                $this->mResult = $db->prepare($this->mSql);

                $this->mResult->execute($this->mArrFieldValue);
            } else {
                $this->mArrField = [];
                $this->mResult = $db->query($this->mSql);
            }

            $this->debugSqlTime();

        }

    }

    function insertUpdate3($pTable, $pOption="") {
        global $db;

        $rowsAffected = 0;
        $update = false;

        if (!$this->mPkZero || $this->mWhere!=='') {

            $this->preparFields('select');

            debugAdd('mArrFieldValue='.print_r($this->mArrFieldValue, true));
            debugAdd('mPk='.$this->mPk);
            debugAdd('mWhere='.$this->mWhere);
            $sql = "SELECT count(*) as quant from $pTable where $this->mPk $this->mWhere; ";
            $this->open($sql);
            $update = ($this->intField('quant')>0);
            debugAdd( 'quant='.$this->intField('quant') );
            debugAdd( 'mRows='.print_r($this->mRows, true) );

        }

        if ($update && (!$this->mPkZero || $this->mWhere!=='')) {

            $this->preparFields('update');

            $sql = "update ".$pTable." set ".$this->mSet." where ".$this->mPk.$this->mWhere."; ";
            $this->debugSql($sql);
            $this->mSql = $sql;

            if (strpos($sql, ':')!=false && count($this->mArrFieldValue)>0) {
                $this->mResult = $db->prepare($this->mSql);

                $this->mResult->execute($this->mArrFieldValue);
            } else {
                $this->mResult = $db->query($this->mSql);
            }

            $this->debugSqlTime();

            $rowsAffected = $this->mResult->rowCount();
            //echo 'rowsAffected='.$rowsAffected;

        } else {

            $this->preparFields('insert');
            debugAdd('mArrFieldValue='.print_r($this->mArrFieldValue, true));

            $sql = "";

            $sql.= $this->mSqlPrevious." ";
            $this->mSqlPrevious = "";

            $sql.= "insert into ".$pTable." (".$this->mCampo.") values (".$this->mValor."); ";
            $this->debugSql($sql);

            $this->mSql = $sql;

            //var_dump($sql);

            if (strpos($sql, ':')!=false && count($this->mArrFieldValue)>0) {
                $this->mResult = $db->prepare($this->mSql);
                if ($this->mDebug) {
                    //debugAdd(print_r($this->mArrFieldValue, true));
                }
                $this->mResult->execute($this->mArrFieldValue);
            } else {
                $this->mArrField = [];
                $this->mResult = $db->query($this->mSql);
            }

            $this->debugSqlTime();

        }//if

    }

    function identity() {
        global $db;
        $this->mArrField = [];
        $this->mArrFieldValue = [];
        $this->mSql = 'select @@identity';
        $this->open();
        return $this->field(0,0);
    }

    function getMaxID($pTable, $pColumn) {
        $this->mArrField = [];
        $this->mArrFieldValue = [];
        $this->mSql = "SELECT isnull(max($pColumn),0)+1 as id from $pTable ";
        $this->open();
        return $this->field('id',0);
    }

    function listColumns() {
        $colunas = "";
        print_r($this->mRows[0]);
        $i = 0;
        //while (list($key, $value) = each($this->mRows[0])) {
        foreach ($this->mRows[0] as $key => $value) {
            if ( is_integer($key) ) {
                $i++;
                if ($i>2) {
                    $colunas .= ',field'.$key;
                }
            }
        }
        return $colunas;
    }
}