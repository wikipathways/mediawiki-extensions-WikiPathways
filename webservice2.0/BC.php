<?php
/**
 * Copyright (C) 2015-2018  J. David Gladstone Institutes
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author tina
 * @author nuno
 * @author anders
 * @author Mark A. Hershberger
 */

namespace WikiPathways\WebService;

use ReflectionMethod;
use finfo;

class BC {

    var $functionArray;
    var $exceptionHandler;

    var $phpdoc;
    var $canPopulateFromPhpDoc = true;

    function __construct( $functionArray ) {
        $this->functionArray = $functionArray;
    }

    function populateFromPhpDoc() {
    }

    function isParamRequired( $functionName, $paramName ) {
    }

    function getSupportedMethod( $functionName ) {
        if (
            is_array( $this->functionArray[$functionName] ) &&
            array_key_exists( "method", $this->functionArray[$functionName] )
        ) {
            if ( is_array( $this->functionArray[$functionName]["method"] ) ) {
                return $this->functionArray[$functionName]["method"];
            } else {
                return [ $this->functionArray[$functionName]["method"] ];
            }
        } else {
            return [ "get" ];
        }
    }

    /*possible properties
      fieldtype, fielddescription
    */
    function getField( $functionName, $fieldName, $property="fieldtype" ) {
        if ( isset( $this->functionArray[$functionName][$property][$fieldName] ) ) {
            return $this->functionArray[$functionName][$property][$fieldName];
        } else {
            return "string";
        }
    }

    /*

     */
    function getFunction( $functioname, $property ) {
        if ( isset( $this->functionArray[$functionName][$property][$fieldName] ) ) {
            return $this->functionArray[$functionName][$property][$fieldName];
        } else {
            return "unknown/mixed";
        }
    }

    function getSwaggerFunctionParameters( $func ) {
        $params = [];

        $fct = new ReflectionMethod( $func );
        $iRequiredParameters = $fct->getNumberOfRequiredParameters();
        $iParameters = $fct->getNumberofParameters();
        $aParameter = $fct->getParameters();
        $parameterCount = 0;

        foreach ( $aParameter as $value ) {
            $parameterCount++;
            foreach ( $value as $index => $val ) {
                $params[] = [
                    "name" => $val,
                    "required" => $parameterCount > $iRequiredParameters ? false : true,
                    "in" => "query",
                    "type" => $this->getField( $func, $val ),
                    "description" => $this->getField( $func, $val, "fielddescription" ),
                    // "type"=>"array",
                    // "collectionFormat"=>"multi"
                ];
            }
        }

        $params[] = [
            "name" => "format",
            "required" => false,
            "in" => "query",
            "type" => "string",
            "default" => "xml",
            "enum" => [ "json","xml","html","dump","jpg","pdf" ],
        ];

        return $params;
    }

    function parseParam( $data ) {
        $data = explode( " ", $data, 3 );

        /*if object, than an object is specified*/
        if ( $param[1] != "object" ) {
            $data["type"] = $data[0];
            $data["param"] = substr( $data[1], 1 );
            $data["description"] = $data[2];
        } else {
            $data = explode( " ", $data, 4 );
            $data["type"] = $data[0];
            $data["param"] = substr( $data[2], 1 );
            $data["description"] = $data[3];
        }
        return $data;
    }

    function getDescription( $funci ) {
        foreach ( $this->functionArray as $func => $description ) {
            $fct = new ReflectionMethod( $func );
            if ( $fct->getDocComment() == false ) {
                $comment = "";
            } else {
                $comment = $func. " true- " .$fct->getDocComment();
            }

            if ( preg_match_all( '/@(\w+)\s+(.*)\r?\n/m', $fct->getDocComment(), $matches ) ) {
                $result = $this->array_combine_( $matches[1], $matches[2] );

                preg_match( '/\*\*(.|\n*)+\* @/', $fct->getDocComment(), $matches );
                $match = $matches[0];
            }

            /*set description*/

            if ( !isset( $this->functionArray[$func][description] ) ) {
                $this->functionArray[$func]["description"] = $result["description"];
            }

            /*set param type and description*/

            if ( is_array( $result["param"] ) ) {
                foreach ( $result["param"] as $param ) {
                    $aParam = $this->parseParam( $param );
                    if ( !isset( $this->functionArray[$func]["fieldtype"][ $aParam["param"] ] ) ) {
                        $this->functionArray[$func]["fieldtype"][ $aParam["param"] ]
                            = $this->swaggerTypeConverter( $aParam["type"] );
                    }
                    if (
                        !isset( $this->functionArray[$func]["fielddescription"][ $aParam["param"] ] )
                    ) {
                        $this->functionArray[$func]["fielddescription"][ $aParam["param"] ]
                            = $aParam["description"];
                    }
                }
            } else {
                if ( isset( $result["param"] ) ) {
                    $aParam = $this->parseParam( $result["param"] );
                    if ( !isset( $this->functionArray[$func]["fieldtype"][ $aParam["param"] ] ) ) {
                        $this->functionArray[$func]["fieldtype"][ $aParam["param"] ]
                            = $this->swaggerTypeConverter( $aParam["type"] );
                    }
                    if (
                        !isset( $this->functionArray[$func]["fielddescription"][ $aParam["param"] ] )
                    ) {
                        $this->functionArray[$func]["fielddescription"][ $aParam["param"] ]
                            = $aParam["description"];
                    }
                }
            }

            if ( $aParam == "array" ) {
                // ***
            }

            /*set return type and description*/
            if ( !isset( $this->functionArray[$func]["returndescription"] ) ) {
                $aReturn = $this->parseParam( $result["return"] );
            }
            $this->functionArray[$func]["returndescription"] = $aReturn["description"];
            $this->functionArray[$func]["returntype"] = $aReturn["type"];
        }
    }

    function array_combine_( $keys, $values ) {
        $result = [];
        foreach ( $keys as $i => $k ) {
            if ( count($values[$i]) == 1 ) {
                $result[$k][] = $values[$i][0];
            } else {
                $result[$k][] = $values[$i];
            }
        }
        return $result;
    }

    function getSwaggerCalls() {
        $swagDesc = [];

        foreach ( $this->functionArray as $func => $description ) {
            $swagDesc["/".$func] = [];
            $methods = [];
            $comment = "";

            $sm = $this->getSupportedMethod( $func );

            $this->getDescription( $func );

            foreach ( $sm as $sm_elem ) {
                $methods[$sm_elem] = [
                    "description" => $comment . $func . (
                        isset( $this->functionArray[$func]["description"] )
                        ? $this->functionArray[$func]["description"]
                        : ""
                    ),
                    "produces" => [ "application/json", "application/xml", "text/html", "text/xml" ],
                    "parameters" => $this->getSwaggerFunctionParameters( $func ),
                    "responses" => [
                        200 => [ "description" => "everything ok" ],

                    ],
                ];
                if ( isset( $this->functionArray[$func]["metatags"] ) ) {
                    $methods[$sm_elem]["tags"] = $this->functionArray[$func]["metatags"];
                }
            }
            $swagDesc["/".$func] = $methods;

        }

        return $swagDesc;
    }

    function getSwagger() {
        $swagger = [
            "swagger" => "2.0",
            "info" => [
                "title" => "WikiPathways Webservices",
                "version" => "1.0"
            ],
            "host" => "webservice.wikipathways.org",
            "schemes" => [ "http" ],
            "basePath" => "/",
            "paths" => $this->getSwaggerCalls()
        ];

        return json_encode( $swagger );
    }

    function setExceptionHandler( $exceptionh ) {
        $this->exceptionHandler = $exceptionh;
    }

    function listen() {
        if ( isset( $_REQUEST["swagger"] ) ) {
            header( 'Content-Type: application/json' );
            echo $this->getSwagger();
        } elseif ( isset( $_REQUEST["describe"] ) && isset( $_REQUEST["method"] ) ) {
            $this->describeMethod();
        } elseif ( isset( $_REQUEST["method"] ) ) {
            // format defaults to XML
            if ( !isset( $_REQUEST["format"] ) ) {
                $_REQUEST["format"] = 'XML';
            }
            $data = $this->executeMethod();

            $this->deliver_response( $_REQUEST["format"], $data );
        } else {
            $this->listWebServices();
        }
    }

    /**
     * Deliver HTTP Response
     * @param string $format The desired HTTP response content type: [json, html, xml]
     * @param string $api_response The desired HTTP response data
     * @return void
     **/
    function deliver_response( $format, $api_response, $functionName = '' ) {
        $http_response_code = [
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found'
        ];

        // Process different content types
        if ( strcasecmp( $format, 'json' ) == 0 ) {
            // Set HTTP Response Content Type
            header( 'Content-Type: application/json; charset=utf-8' );
            // Format data into a JSON response
            $json_response = json_encode( $api_response );
            // Deliver formatted data
            echo $json_response;

        } elseif ( strcasecmp( $format, 'xml' ) == 0 ) {
            // Set HTTP Response Content Type
            header( 'Content-Type: application/xml; charset=utf-8' );

            // Format data into an XML response (This is only good at
            // handling string data, not arrays)
            $method = $_REQUEST["method"];
            $xml_response = "<ns1:" . $method . "Response xmlns:ns1='http://www.wso2.org/php/xsd' "
                          . "xmlns:ns2='http://www.wikipathways.org/webservice'>\t"
                          . $this->arrayToXml( $api_response, "ns1" )
                          . "\n</ns1:".$method."Response>";

            echo $xml_response;

        } elseif (
            is_string( $api_response )
            && ( strcasecmp( $format, 'jpg' ) == 0
                 || strcasecmp( $format, 'pdf' ) == 0
                 || strcasecmp( $format, 'png' ) == 0
            )
        ) {
            // Set HTTP Response Content Type (This is only good at
            // handling string data, not arrays)
            $finfo = new finfo( FILEINFO_MIME );
            $mime = $finfo->buffer( $api_response );

            // Deliver formatted data
            header( "Content-Type: $mime" );
            echo $api_response;
        } else {
            // Set HTTP Response Content Type (This is only good at
            // handling string data, not arrays)

            // Deliver formatted data
            header( 'Content-Type: text/html; charset=utf-8' );
            echo "<pre>";
            var_dump( $api_response );
            echo "</pre>";
        }
        // End script process
        exit;
    }

    /**
     * Displays a list of webservices
     * @return void
     */
    function listWebServices() {
        ksort( $this->functionArray );
        echo "<h1>List of services available</h1>";
        foreach ( $this->functionArray as $name => $value ) {
            echo "<h2><a href='?method=$name&describe'>".$name."</a></h2>";
        }
        exit;
    }

    /**
     * Executes a method
     * @return array
     */

    function executeMethod() {
        $_wservices = $this->functionArray;
        $aInvokeParameter = [];
        $response = "";

        try{

            $fct = new ReflectionMethod( $_REQUEST["method"] );
            $iRequiredParameters = $fct->getNumberOfRequiredParameters();
            $aParameter = $fct->getParameters();

            foreach ( $aParameter as $value ) {
                foreach ( $value as $index => $val ) {
                    if (
                        isset( $_wservices[$_REQUEST['method']]['fieldtype'][$val] )
                        && isset( $_wservices[$_REQUEST['method']]['fieldtype'][$val] ) === 'file'
                    ) {
                        $aInvokeParameter[] = $val;
                    } elseif (
                        isset( $_wservices[$_REQUEST['method']]['fieldtype'][$val] )
                        && $_wservices[$_REQUEST['method']]['fieldtype'][$val] === 'array'
                    ) {
                        if ( isset( $_REQUEST[$val] ) ) {
                            $parameters = $this->getMultipleParameters( $val );
                            $aInvokeParameter[] = $parameters;
                        }
                    } else {
                        if ( isset( $_REQUEST[$val] ) ) {
                            $aInvokeParameter[] = $_REQUEST[$val];
                        }
                    }
                }
            }

            $response = $fct->invokeArgs( $aInvokeParameter );
        } catch ( Exception $e ) {
            if ( is_callable( $this->exceptionHandler ) ) {
                $deffunc = $this->exceptionHandler;
                $response = $deffunc( $e );
            }
        }

        return $response;
    }

    /**
     * Describes a method   // displays it in HTML
     */
    function describeMethod() {
        $fct = new ReflectionMethod( __NAMESPACE__ . '\\Call::' . $_REQUEST["method"] );
        $iRequiredParameters = $fct->getNumberOfRequiredParameters();
        $iParameters = $fct->getNumberofParameters();
        $aParameter = $fct->getParameters();
        echo "<h1>".$_REQUEST['method']."</h1>";

        $iCountRequired = 0;

        $_wservices = $this->functionArray;

        if ( isset( $_wservices[$_REQUEST['method']]['method'] ) ) {
            $method = $_wservices[$_REQUEST['method']]['method'];
        } else {
            $method = "GET";
        }

        echo "<form action='index.php' method='".$method."' enctype='multipart/form-data'>";

        foreach ( $aParameter as $value ) {
            foreach ( $value as $index => $val ) {
                if ( isset( $_wservices[$_REQUEST['method']]['fieldDescription'][$val] ) ) {
                    $description = $_wservices[$_REQUEST['method']]['fieldDescription'][$val];
                } else {
                    $description = '';
                }
                $type = isset( $_wservices[$_REQUEST['method']]['fieldtype'][$val] )
                      ? $_wservices[$_REQUEST['method']]['fieldtype'][$val]
                      : 'text';

                if ( $iCountRequired < $iRequiredParameters ) {
                    if ( $type == 'textarea' ) {
                        echo "<b>".$val. "</b> <textarea name='$val' ></textarea> $description<br/>";
                    } else {
                        echo "<b>".$val. "</b> <input type='$type' name='$val' /> $description<br/>";
                    }

                } else {
                    if ( $type == 'textarea' ) {
                        echo $val. " <textarea name='$val' ></textarea> $description<br/>";
                    } else {
                        echo $val. " <input type='$type' name='$val' /> $description<br/>";
                    }
                }
                $iCountRequired++;
            }
        }

        echo '<p><b>Bold:</b> required parameters</p>';

        echo "<input type='hidden' name='method' value='".$_REQUEST['method']."'>";
        echo "<input type='radio' name='format' value='json' checked='checked'> JSON";
        echo "<input type='radio' name='format' value='xml'> XML";
        echo "<input type='radio' name='format' value='html'> HTML";
        echo "<input type='radio' name='format' value='jpg'> JPG (not all functions support it)";
        echo "<input type='radio' name='format' value='pdf'> PDF (not all functions support it)";
        echo "<input type='radio' name='format' value='png'> png (not all functions support it)";
        echo "<br/><br/>";

        echo "<input type='submit' /></form>";

        // print_r($aInvokeParameter);
    }

    private function isObjectArray( $obj ) {
        $is_numerical_array = true;
        $convArr = (array)$obj;

        foreach ( $convArr as $key => $value ) {
            if ( !is_numeric( $key ) ) {
                $is_numerical_array = false;
            }
        }

        return $is_numerical_array;
    }

    private function arrayToXml( $array, $namespace = '', $deftag = '', $level=1 ) {
        $xml = "";
        $debug = false;

        if ( $debug ) {
            print_r( $array );
        }

        if ( !is_array( $array ) ) {
            $array = [ 'Result' => $array ];
        }

        if ( $level < 2 ) {
            $namespace = "ns1";
        } else {
            $namespace = "ns2";
        }

        foreach ( $array as $key => $value ) {
            if ( is_array( $value ) ) {
                if ( $debug ) {
                    echo "a. processing ".$key ." $level\n";
                }
                /* didn't incremente level */
                $xml .= $this->arrayToXml( $value, $namespace, $key, $level + 0 );
            } elseif ( is_object( $value ) ) {
                if ( !is_numeric( $deftag ) && strlen( $deftag ) > 0 ) {
                    $stag = $namespace != ''
                          ? $namespace . ":" . $deftag
                          : $deftag;
                } else {
                    $stag = $namespace != ''
                          ? $namespace . ":" . $key
                          : $key;
                }

                if ( $debug ) {
                    echo "0. processing " . $key . " -  " . $stag . " - " . $deftag . " $level\n";
                }

                if ( $this->isObjectArray( $value ) ) {
                    // should we increase level?
                    $xml .= $this->arrayToXml( ( (array)$value ), $namespace, $key, $level + 1 );
                } else {
                    $xml .= "\n".str_repeat( "\t", $level )."<$stag>"
                         . $this->arrayToXml( ( (array)$value ), $namespace, $key, $level + 1 )
                         . "\n".str_repeat( "\t", $level )."</$stag>";
                }

            } else {
                $value = htmlentities( $value );

                if ( strlen( $deftag ) > 0 && is_integer( $key ) ) {
                    if ( $debug ) {
                        echo "1. processing ".$key . " " . $stag . " $level\n";
                    }
                    $stag = $namespace != '' ? $namespace . ":" . $deftag : $deftag;
                    $xml .= "\n".str_repeat( "\t", $level )."<$stag>$value</$stag>";
                } else {
                    if ( $debug ) {
                        echo "2. processing ".$key . " " . $stag . " $level\n";
                    }
                    $stag = $namespace != '' ? $namespace . ":" . $key : $key;
                    $xml .= "\n".str_repeat( "\t", $level )."<$stag>$value</$stag>";
                }
            }
        }
        return $xml;
    }

    private function getMultipleParameters( $kval ) {
        $ret = [];

        $query = $_SERVER['QUERY_STRING'];
        $vars = [];
        $second = [];
        foreach ( explode( '&', $query ) as $pair ) {
            list( $key, $value ) = explode( '=', $pair );
            if ( '' == trim( $value ) ) {
                continue;
            }

            if ( $key === $kval ) {
                $ret[] = $value;
            }
        }

        return $ret;
    }

    private function swaggerTypeConverter( $t ) {
        $type["int"] = "integer";
        $type["integer"] = "integer";
        $type["long"] = "integer";
        $type["float"] = "number";
        $type["double"] = "number";
        $type["string"] = "string";
        $type["byte"] = "string";
        $type["boolean"] = "boolean";
        $type["bool"] = "boolean";
        $type["date"] = "string";
        $type["dateTime"] = "string";
        $type["password"] = "string";

        if ( isset( $type[$t] ) ) {
            return $type[$t];
        } else {
            return $t;
        }
    }
}
