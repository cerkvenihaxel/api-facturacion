 PDF To Markdown Converter
Debug View
Result View
AFIP
Manuales para el desarrollador
Facturación Electrónica
Emisión de Bonos Fiscales Electrónicos v3.

R.G. N° 5427/2023 Y R.G. N° 2.
Agencia de Recaudación y Control Aduanero
Subdirección General de Sistemas y
Telecomunicaciones
ARCA-SDG SIT
Revisión correspondiente al 9 de Junio de 202 5
Historial de modificaciones

Ver Fecha Edición Descripción
1.1 21-03-2011 SDG SIT/DIF Versión inicial del documento
2.0 01-09-2018 SDG SIT/DIF Alta de Comprobantes Asociados, se modifica validación 1014
El campo fecha_cbte (yyyymmdd) puede ser hasta 5 días an-
teriores o posteriores respecto de la fecha de generación. La
misma no podrá exceder el mes de presentación.
2.1 01-10-2018 SDG SIT/DIF Se amplía el campo punto de venta de numérico de 4 a numé-
rico de 5.
Se modifica la estructura general de los mensajes de
respuesta incluyendo información adicional al header del
response (ver punto 1.4).
Se modifica validación 1032
Se modifica validación 1014. El tipo de cambio no podrá ser
inferior al 20% ni superior en un 100% del que suministra
ARCA como orientativo de acuerdo a la cotización oficial.
2.2 Beta 1 28-12-2018 SDG SIT/DIF Se agrega funcionalidad para soportar comprobantes de Fac-
tura de Crédito (MiPyMEs)

Se agregan los sig. tags
<Cmp><Fecha_vto_pago>
<Cmp><CbtesAsoc><CbteAsoc><Cuit>
<Cmp><CbtesAsoc><CbteAsoc><Cuit><Fecha_cbte>
Se modifican los siguientes códigos para modalidad de autori-
zación CAE: 1035
Se dan de alta los siguientes códigos para modalidad de auto-
rización CAE: 4880, 4881, 4882, 4886, 4887, 4888, 4889,
4890, 4891, 4892, 4893, 4894, 4895, 4896, 4897, 4898, 4900,
4901, 4902, 4903, 4905, 4906, 4907, 4908, 4909, 4910, 4911,
4912, 4913, 4914, 4915, 4916, 4917
2.2 Beta 2 16-01-2019 SDG SIT/DIF Se modifican los códigos 4883,

Se dan de baja los códigos 4899, 4903, 4904
Se dan de alta los códigos del 4918 al 4930
2.2 11-03-2019 SDG SIT/DIF Se modifican los códigos 4881
Se agregan los códigos 4916, 4931, 4932, 4933, 4934
2.3 01-05-2019 SDG SIT/DIF Se agregan los siguientes códigos de validación: 4945. 4946,
4947, 4948, 4949, 4950, 4951
Se modifican los siguientes códigos: 4891, 4903
2.4 01-11-2019 SDG SIT/DIF Se agrega a modo observación el código 21 en la estructura
BFEResultAuth del método Autorizador (BFEAuthorize)
2.5 10-08-2020 SDG SIT/DIF Adecuación para que el campo opcional 23 – Referencia Co-
mercial, deje de ser exclusivo para Facturas de Crédito.
Se modifica validación del código 4916, 4931
2.6 29-01-2021 SDG SIT/DIF Se adaptan los métodos públicos con el fin de incorporar me-
diante códigos Opcionales y solo para las facturas de crédito
el identificar que representa si se transfiere al sistema de cir-
culación abierta o al agente de depósito colectivo.
Se modifican los códigos 4910, 4916
Se dan de alta los códigos 4952, 4953 y 4954
2.7 01-06-2021 SDG SIT/DIF ADECUACIONES LEY 27.618 “REGIMEN DE SOSTENI-
MIENTO E INCLUSIÓN FISCAL PARA PEQUEÑOS COTRI-
BUYENTES”
Se agrega a modo de observación el código 22 en la estructu-
ra BFEResultAuth del método Autorizador (BFEAuthorize)
2.8 13-09-2021 SDG SIT/DIF
Se modifica observación del código 22 en la estructura BFE-
ResultAuth del método Autorizador (BFEAuthorize)
2.9 17-02-2022 SDG SIT/DIF
Se agrega validación 4955 para dar soporte a las facturas de
crédito
2.10 01-04-2022 SDG SIT/DIF

Se agrega validación 4956 para evitar asociar comprobantes
que fueron autorizados con puntos de venta que pertenecen a
distintos regímenes de facturación.
2.11 01-01-2023 SDG SIT/DIF

Se modifica validación 1014. El tipo de cambio no podrá ser
inferior al 20% ni superior en un 200% del que suministra
ARCA como orientativo de acuerdo a la cotización oficial.
2.12 01-01-2024 SDG SIT/DIF ADECUACIONES de Monitoreo

Se agrega a modo de observación los códigos 23, 24 y 25 en
la estructura BFEResultAuth del método Autorizador (BFEAu-
thorize)
3.0 17-0 3 -202 5 SDG SIT/DIF Modificación de los siguientes métodos:

BFEAuthorize
BFEGetCMP
Agregando los siguientes campos:
< CondicionIVAReceptorId >
<CanMisMonExt>
en las siguientes estructuras:
<BFEAuthorize> / <Cmp>
<BFEGetCMPResponse> / <BFEResultGet>
Si se indica que el pago del comprobante se realiza en la
misma moneda extranjera que la factura, la cotización de la
moneda provista debe coincidir exactamente con la registrada
en las bases de ARCA para el día hábil anterior a la fecha de
emisión del comprobante, si esta es anterior a la fecha actual,
o bien con la registrada para el día hábil anterior a la fecha
actual, si la fecha de emisión es posterior a esta. En caso
contrario, se puede omitir el campo de Cotización de Moneda.
Se agregan los siguientes métodos nuevos:
- BFEGetPARAM_CondicionIvaReceptor para recuperar los
valores de referencia de los códigos correspondientes a la
condición de IVA del receptor.

BFEGetCotizacion para recuperar la cotización de una
moneda determinada a un fecha.
Ademas se agregan los siguientes códigos de rechazo 4957,
4958, 4959, 4960, 4961 y 4962, 4963 y de observación el
codigo 26 en la estructura BFEResultAuth del método
Autorizador (BFEAuthorize).
También se agregan los códigos 4967 en validaciones del
métodos BFEGetPARAM_CondicionIvaReceptor y los
códigos 4964, 4965 y 4966 en validaciones del método
BFEGetCotizacion respectivamente.
A partir del 6 de abril de 2025 podrá enviarse de forma
opcional el campo Condición Frente al IVA del receptor, hasta
tanto entre en vigencia su obligatoriedad reglamentada por la
Resolución General N°5616, en cuyo momento pasará a
rechazar la emisión de comprobantes sin este dato.
3.1 20-0 5 -202 5 SDG SIT/DIF Se modifica el código de validación del método
BFEGetPARAM_CondicionIvaReceptor. Se cambia el
código 4963 de dicho método por el 4967.

3.2 09-0 6 -202 5 SDG SIT/DIF Será obligatorio el campo Condición Frente al IVA del
receptor, atento a la entrada en vigencia reglamentada por la
Resolución General N°5616. Por tal motivo el código de
observacion 26 quedará en desuso.

Contenido
1 INTRODUCCIÓN................................................................................................................................
1.1 OBJETIVO..........................................................................................................................................
1.2 ALCANCE...........................................................................................................................................
1.3 AUTENTICACIÓN.................................................................................................................................
1.4 ESTRUCTURA GENERAL DEL MENSAJE DE RESPUESTA (RESPONSE)......................................................
1.5 TRATAMIENTO DE ERRORES EN EL WS................................................................................................
1.6 TRATAMIENTO DE EVENTOS EN EL WS................................................................................................
1.7 DIRECCIÓN URL................................................................................................................................
1.8 CANALES DE ATENCIÓN....................................................................................................................
1.9 SITIOS DE CONSULTA.......................................................................................................................
2 WS DE NEGOCIO............................................................................................................................
2.1 AUTORIZADOR (BFEAUTHORIZE)......................................................................................................
2.1.1 DIRECCIÓN URL...........................................................................................................................
2.1.2 MENSAJE DE SOLICITUD................................................................................................................
2.1.3 MENSAJE DE RESPUESTA..............................................................................................................
2.1.4 VALIDACIONES DE ESTRUCTURA Y ERRORES..................................................................................
2.1.5 VALIDACIONES DE CABECERA Y ERRORES......................................................................................
2.1.6 VALIDACIONES DE NEGOCIO Y ERRORES........................................................................................
2.1.7 OTROS ERRORES..........................................................................................................................
VALIDACIONES NO EXCLUYENTES:.............................................................................................................
2.2 RECUPERADOR DE COMPROBANTE (BFEGETCMP)..........................................................................
2.2.1 DIRECCIÓN URL...........................................................................................................................
2.2.2 MENSAJE DE SOLICITUD................................................................................................................
2.2.3 MENSAJE DE RESPUESTA..............................................................................................................
2.2.4 ERRORES.....................................................................................................................................
2.3 RECUPERADOR DE VALORES REFERENCIALES DE CÓDIGOS DE MONEDA (BFEGETPARAM_MON)....
2.3.1 DIRECCIÓN URL...........................................................................................................................
2.3.2 MENSAJE DE SOLICITUD................................................................................................................
2.3.3 MENSAJE DE RESPUESTA..............................................................................................................
2.3.4 VALIDACIONES, ACCIONES Y ERRORES...........................................................................................
2.4 RECUPERADOR DE VALORES REFERENCIALES DE CÓDIGOS DE PRODUCTOS (BFEGETPARAM_NCM)
2.4.1 DIRECCIÓN URL...........................................................................................................................
2.4.2 MENSAJE DE SOLICITUD................................................................................................................
2.4.3 MENSAJE DE RESPUESTA..............................................................................................................
2.4.4 VALIDACIONES, ACCIONES Y ERRORES...........................................................................................
(BFEGETPARAM_TIPO_CBTE)................................................................................................................. 2.5 RECUPERADOR DE VALORES REFERENCIALES DE CÓDIGOS DE TIPOS DE COMPROBANTE
2.5.1 DIRECCIÓN URL...........................................................................................................................
2.5.2 MENSAJE DE SOLICITUD................................................................................................................
2.5.3 MENSAJE DE RESPUESTA..............................................................................................................
2.5.4 VALIDACIONES, ACCIONES Y ERRORES...........................................................................................
(BFEGETPARAM_TIPO_IVA).................................................................................................................... 2.6 RECUPERADOR DE VALORES REFERENCIALES DE CÓDIGOS ALÍCUOTAS DE IVA
2.6.1 DIRECCIÓN URL...........................................................................................................................
2.6.2 MENSAJE DE SOLICITUD................................................................................................................
2.6.3 MENSAJE DE RESPUESTA..............................................................................................................
2.6.4 VALIDACIONES, ACCIONES Y ERRORES...........................................................................................
2.7 RECUPERADOR DE VALORES REFERENCIALES DE CÓDIGOS DE ZONA (BFEGETPARAM_ZONAS).......
2.7.1 DIRECCIÓN URL...........................................................................................................................
2.7.2 MENSAJE DE SOLICITUD................................................................................................................
2.7.3 MENSAJE DE RESPUESTA..............................................................................................................
2.7.4 VALIDACIONES, ACCIONES Y ERRORES...........................................................................................
(BFEGETPARAM_TIPO_OPC)................................................................................................................. 2.8 RECUPERADOR DE VALORES REFERENCIALES DE CÓDIGOS DE OPCIONALES
2.8.1 DIRECCIÓN URL...........................................................................................................................
2.8.2 MENSAJE DE SOLICITUD................................................................................................................
2.8.3 MENSAJE DE RESPUESTA..............................................................................................................
2.8.4 VALIDACIONES, ACCIONES Y ERRORES...........................................................................................
(BFEGETPARAM_CONDICIONIVARECEPTOR)........................................................................................... 2.9 RECUPERADOR DE VALORES REFERENCIALES DE CÓDIGOS DE CONDICIÓN DE IVA DEL RECEPTOR
2.9.1 DIRECCIÓN URL...........................................................................................................................
2.9.2 MENSAJE DE SOLICITUD................................................................................................................
2.9.3 MENSAJE DE RESPUESTA..............................................................................................................
2.9.4 VALIDACIONES, ACCIONES Y ERRORES...........................................................................................
2.10 RECUPERADOR DE COTIZACIONES REGISTRADAS EN EL ORGANISMO (BFEGETCOTIZACION)............
2.10.1 DIRECCIÓN URL.........................................................................................................................
2.10.2 MENSAJE DE SOLICITUD..............................................................................................................
2.10.3 MENSAJE DE RESPUESTA............................................................................................................
2.10.4 VALIDACIONES, ACCIONES Y ERRORES.........................................................................................
3 ANEXOS...........................................................................................................................................
3.1 CONDICIÓN FRENTE AL IVA DEL RECEPTOR.........................................................................................
1 INTRODUCCIÓN................................................................................................................................
1.1 OBJETIVO..........................................................................................................................................
Este documento está dirigido a quienes tengan que desarrollar el cliente consumidor de los
WebServices correspondientes al servicio de Facturación Electrónica - Bonos Fiscales elec-

trónicos (WSBFEv1).

1.2 ALCANCE...........................................................................................................................................
Este documento brinda las especificaciones técnicas para desarrollar el cliente de WebSer-

vices para usar el WSBFEv1. Debe complementarse con los documentos relativos a: Servi-
cio de Autenticación y Autorización y Establecimiento del canal de comunicación.

1.3 AUTENTICACIÓN.................................................................................................................................
Para utilizar cualquiera de los métodos disponibles en el presente WS es necesario un Ti-
cket de Acceso provisto por el WS de Autenticación y Autorización (WSAA).

Recordar que para consumir el WS de Autenticación y Autorización WSAA es necesario ob-
tener previamente un certificado digital desde clave fiscal y asociarlo al ws de negocio
"Bonos Fiscales Electrónicos - BFE".

Al momento de solicitar un Ticket de Acceso por medio del WS de Autenticación y Autoriza-
ción WSAA tener en cuenta que debe enviar el tag service con el valor "wsbfe" y que la du-
ración del mismo es de 12 hs.

Para más información deberá redirigirse a los manuales http://www.afip.gob.ar/ws.

1.4 ESTRUCTURA GENERAL DEL MENSAJE DE RESPUESTA (RESPONSE)......................................................
Los mensajes de respuesta que se transmiten tienen implementado el subelemento

FEHeaderInfo contenido en el elemento opcional Header, que se contempla en la estructura
SOAP. En este webservice se utiliza para brindar información contextual relacionada con el
proceso del mensaje. El procesamiento de dicha información no es obligatoria en los

respectivos clientes, pero contribuye con información contextual de procesamiento que es
de utilidad ante posibles eventualidades.

Ejemplo de mensaje de respuesta en el ambiente de Testing

<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema">

<soap:Header>


Desarrollo - Clo

2018-09-27T13:02:06.2033495-03:00

1.0.3.0


</soap:Header>

<soap:Body>

</soap:Body>

</soap:Envelope>

Ejemplo de mensaje de respuesta en el ambiente de Producción

<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"

xmlns:xsd="http://www.w3.org/2001/XMLSchema">

<soap:Header>


Produccion - Pto

2018-09-27T13:02:06.2033495-03:00

1.0.3.0


</soap:Header>

<soap:Body>

</soap:Body>

</soap:Envelope>

1.5 TRATAMIENTO DE ERRORES EN EL WS................................................................................................
El tratamiento de errores en todos los servicios se realizará de la siguiente manera:

<s:element minOccurs=”0” maxOccurs=”1”
name=”BFEErr” type=”tns:ClsBFEErr” />
...
<s:sequence>
<s:element minOccurs=”1” maxOccurs=”1”
name=”errcode” type=”s:int” />
<s:element minOccurs=”0” maxOccurs=”1”
name=”errmsg” type=”s:string” />
</s:sequence>
Dónde:

Campo Detalle Obligatorio
BFEErr Información correspondiente al error. Contiene los datos de
errcode y errmsg
S
Errcode Código de error S
Errmsg Mensaje de error S
Para errores internos de infraestructura, los errores se devuelven en la misma estructura

(BFEerror). Los códigos de error son:

Código de error Mensaje de error
500 Error interno de aplicación.
501 Error interno de base de datos.
502 Error interno – Autorizador - Transacción Activa
1.6 TRATAMIENTO DE EVENTOS EN EL WS................................................................................................
Todos los métodos del Web service cuentan con una sección para la comunicación de

eventos de ARCA para los clientes, los mismos tienes dos campos eventcode y eventmsg,
en el primero contiene el Identificador de mensaje y el segundo, es el mensaje propiamente
dicho. Ejemplo eventid=1 eventmsg=” Por razones de mantenimiento este ws estará fuera
de línea el 1 de enero del 2020”

<s:element minOccurs=” 0 ” maxOccurs=” 1 ”
name=” BFEEvents ” type=” tns:ClsBFEEvents ” />
<s:sequence>
<s:element minOccurs=”1” maxOccurs=”1”
name=”eventcode” type=”s:int” />
<s:element minOccurs=”0” maxOccurs=”1”
name=”eventmsg” type=”s:string” />
</s:sequence>
Dónde:

Campo Detalle Obligatorio
BFEEvents Información correspondiente a eventos. S
Eventcode Código de evento ( 9 tem 9 e irrepetible) S
Eventmsg Mensaje S
1.7 DIRECCIÓN URL................................................................................................................................
Este servicio se llama en Homologación desde:
https://wswhomo.afip.gov.ar/wsbfev1/service.asmx
Para visualizar el WSDL en Homologación:
https://wswhomo.afip.gov.ar/wsbfev1/service.asmx?WSDL
Este servicio se llama en Producción desde:
https://servicios1.afip.gov.ar/wsbfev1/service.asmx
Para visualizar el WSDL en Producción:
https://servicios1.afip.gov.ar/wsbfev1/service.asmx?WSDL
1.8 CANALES DE ATENCIÓN....................................................................................................................
Consultas sobre el ambiente de homologación :
Acerca de certificados y accesos, consultar sitio http://www.afip.gob.ar/ws/
Consultas sobre el ambiente de producción :
sri@arca.gov.ar
Consultas sobre normativa:
facturaelectronica@arca.gov.ar
1.9 SITIOS DE CONSULTA.......................................................................................................................
Biblioteca Electrónica
ABC – Consultas y Respuestas Frecuentes sobre:
Funcionalidades del WS
Normativa, Aplicativos y Sistemas. Opción Facturación y Registración
Documentación de Ayuda
http://www.afip.gob.ar/fe/ayuda.asp.
2 WS DE NEGOCIO............................................................................................................................
2.1 AUTORIZADOR (BFEAUTHORIZE)......................................................................................................
2.1.1 DIRECCIÓN URL...........................................................................................................................
Este servicio se llama desde (entorno de homologación):

http://wswhomo.afip.gov.ar/wsbfev1/service.asmx

Service.asmx es el webservice global cada uno de sus métodos es invocado con esta url

mas el parámetro op con el nombre del método

Ejemplo

http://wswhomo.afip.gov.ar/wsbfev1/service.asmx?op=BFEAuthorize

2.1.2 MENSAJE DE SOLICITUD................................................................................................................
Recibe la información de factura/lote de ingreso.

<?xml version=”1.0” encoding=”utf-8”?>
<soap:Envelope xmlns:xsi=”http://www.w3.org/2001/XMLSchema-
instance” xmlns:xsd=”http://www.w3.org/2001/XMLSchema”
xmlns:soap=”http://schemas.xmlsoap.org/soap/envelope/”>
<soap:Body>
<BFEAuthorize xmlns=”http://ar.gov.afip.dif.bfev1/”>
<Auth>
<Token> string </Token>
<Sign> string </Sign>
<Cuit> long </Cuit>
</Auth>
<Cmp>
<Id> long </Id>
<Tipo_doc> short </Tipo_doc>
<Nro_doc> long </Nro_doc>
<Zona> short </Zona>
<Tipo_cbte> short </Tipo_cbte>
<Punto_vta> int </Punto_vta>
<Cbte_nro> long </Cbte_nro>
<Imp_total> double </Imp_total>
<Imp_tot_conc> double </Imp_tot_conc>
<Imp_neto> 11 tem 11 o </Imp_neto>
<Impto_liq> double </Impto_liq>
<Impto_liq_rni> double </Impto_liq_rni>
<Imp_op_ex> double </Imp_op_ex>
<Imp_perc> double </Imp_perc>
<Imp_iibb> double </Imp_iibb>
<Imp_perc_mun> double </Imp_perc_mun>
<Imp_internos> double </Imp_internos>
<Imp_moneda_Id> string </Imp_moneda_Id>
<Imp_moneda_ctz> double </Imp_moneda_ctz>
<Fecha_cbte> string </Fecha_cbte>
<Fecha_vto_pago> string </Fecha_vto_pago>
<CondicionIVAReceptorId> int </CondicionIVAReceptorId>
<CanMisMonExt> string </CanMisMonExt>
<Opcionales>
<Opcional>
<Id> string </Id>
<Valor> string </Valor>
</Opcional>
<Opcional>
<Id> string </Id>
<Valor> string </Valor>
</Opcional>
</Opcionales>
<Items>
<Item>
<Pro_codigo_ncm> string </Pro_codigo_ncm>
<Pro_codigo_sec> string </Pro_codigo_sec>
<Pro_ds> string </Pro_ds>
<Pro_qty> double </Pro_qty>
<Pro_umed> int </Pro_umed>
<Pro_precio_uni> double </Pro_precio_uni>
<Imp_bonif> double </Imp_bonif>
<Imp_total> double </Imp_total>
<Iva_id> short </Iva_id>
</Item>
<Item>
<Pro_codigo_ncm> string </Pro_codigo_ncm>
<Pro_codigo_sec> string </Pro_codigo_sec>
<Pro_ds> string </Pro_ds>
<Pro_qty> double </Pro_qty>
<Pro_umed> int </Pro_umed>
<Pro_precio_uni> double </Pro_precio_uni>
<Imp_bonif> double </Imp_bonif>
<Imp_total> double </Imp_total>
<Iva_id> short </Iva_id>
</Item>
</Items>
<CbtesAsoc>
<CbteAsoc>
<Tipo_cbte> short </Tipo_cbte>
<Punto_vta> int </Punto_vta>
<Cbte_nro> long </Cbte_nro>
<Cuit> string </Cuit>
<Fecha_cbte> string </Fecha_cbte>
</CbteAsoc>
<CbteAsoc>
</CbtesAsoc>
</Cmp>
</BFEAuthorize>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Detalle Obligatorio
Auth Información de la autenticación. Contiene los datos de Token,
Sign , Cuit e Id
S
Token Token devuelto por el WSAA S
Sign (^) Sign devuelto por el WSAA S
Cuit Cuit contribuyente (representado o Emisora) S
Campo Detalle Obligatorio
Cmp Información de la factura de ingreso. Contiene los datos de la
cabecera del comprobante y sus ítems

S
Items Información de los ítems que componen el documento a au-
torizar
S
CbtesAsoc Información de los comprobantes asociados al comprobante
que se está por autorizar
N
Cmp : La cabecera del comprobante está compuesta por los siguientes campos:

Campo Tipo Detalle Obligatorio
Id Long Identificador del requerimiento
Tipo_doc Int Código de documento del comprador S
Nro_doc Long Nro. De identificación del comprador S
Zona Short Código de zona S
tipo_cbte Int Tipo de comprobante (BFEGetPARAM_Tipo_-
Cbte)
S
Punto_vta Int Punto de venta S
Cbt_nro Long Nro. De comprobante S
imp_total Double Importe total de la operación S
imp_tot_conc Double Importe total de conceptos que no integran el
precio neto gravado
S
imp_neto Double Importe neto gravado S
impto_liq Double Importe liquidado S
impto_liq_rni Double Impuesto liquidado a RNI o percepción a no ca-
tegorizados
S
imp_op_ex Double Importe de operaciones exentas S
Imp_perc Double Importe de percepciones S
Imp_internos Double Importe de impuestos internos S
Imp_moneda_Id Double Código de moneda(BFEGetPARAM_MON) S
Imp_moneda_ctz Double Cotización de moneda. De informar el campo,
el mismo no puede quedar vacío.
N
Fecha_cbte String Fecha de comprobante (yyyymmdd) S
Fecha_vto_pago String Fecha de comprobante (yyyymmdd) N
CondicionIVARe-
ceptorId
Int Identificador de la condición frente al IVA del
receptor. De informarse debe corresponder a la
tabla “Condición frente al IVA del receptor”. Ver
Anexo
N
CanMisMonExt String Marca que identifica si el comprobante se
cancela en misma moneda del comprobante
(moneda extranjera). Valores posibles S o N.
N
Items Item Detalle de ítem S
Opcionales Opcional Detalle de opcionales N
CbtesAsoc CbteAsoc Detalle de Comprobantes Asociados N
Opcionales : sección para informar campos opcionales:

Campo Tipo Detalle Obligatorio
Id String Código del tipo de opcional. Los id aceptados
se obtienen del 13 tem 13 o BFEGetPARAM_Ti-
po_Opc
S
Valor String Valor a registrar S
Items : el detalle de los ítems del comprobante está compuesto por los siguientes campos:

Campo Tipo Detalle Obligatorio
Pro_codigo_ncm String Código de producto (nomenclador 13 del MER-
COSUR)
S
Pro_codigo_sec String Código de producto según Secretaria N
Pro_ds String Descripción del producto S
Pro_qty Double Cantidad S
Pro_umed Int Código de unidad de medida (BFEGetPARA-
M_Umed)
S
Pro_precio_uni Double Precio unitario S
Imp_bonif Double Importe bonificación S
Imp_total Double Importe total S
Iva_id Int Código de IVA (BFEGetPARAM_Tipo_IVA) S
CbtesAsoc : detalle de campos de los comprobantes asociados

Campo Tipo Detalle Obligatorio
Tipo_cbte Short Tipo de comprobante que se puede asociar S
Punto_vta int Punto de venta S
Cbte_nro Long Nro. de comprobante S
Cuit String Cuit emisor del comprobante Asociado N
Fecha_cbte String Fecha emisión del comprobante Asociado N
Aclaración

Si se envía una Factura a autorizar (cbte tipo 01 o 06), solo puede asociarse el tipo de cbte
= 91 remito.

Si se envía una Nota de débito o crédito a autorizar (cbte tipo 02 o 03), solo puede asociarse
el tipo de cbte = 01 factura A, 02 Nota de débito A , 03 Nota de crédito A o 91 remito.

Si se envía una Nota de débito o crédito a autorizar (cbte tipo 07 o 08), solo puede asociarse
el tipo de cbte = 06 factura B, 07 Nota de débito B , 08 Nota de crédito B o 91 remito.

2.1.3 MENSAJE DE RESPUESTA..............................................................................................................
Retorna la información del comprobante de ingreso agregándole el CAE otorgado. Ante

cualquier anomalía se retorna un código de error cancelando la ejecución del WS.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi=”http://www.w3.org/2001/XMLSchema-
instance” xmlns:xsd=”http://www.w3.org/2001/XMLSchema”
xmlns:soap=”http://schemas.xmlsoap.org/soap/envelope/”>
<soap:Body>
<BFEAuthorizeResponse xmlns=”http://ar.gov.afip.dif.bfev1/”>
<BFEAuthorizeResult>
<BFEResultAuth>
<Id> long </Id>
<Cuit> long </Cuit>
<Cae> string </Cae>
<Fch_venc_Cae> string </Fch_venc_Cae>
<Fch_cbte> string </Fch_cbte>
<Resultado> string </Resultado>
<Reproceso> string </Reproceso>
<Obs> string </Obs>
</BFEResultAuth>
<BFEErr>
<ErrCode> int </ErrCode>
<Errmsg> string </Errmsg>
</BFEErr>
<BFEEvents>
<EventCode> int </EventCode>
<EventMsg> string </EventMsg>
</BFEEvents>
</BFEAuthorizeResult>
</BFEAuthorizeResponse>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Detalle Obligatorio
BFEAuthorizeResult Información del comprobante de ingreso, conteniendo el
CAE otorgado. Contiene los datos de BFEResultAuth ,
BFEErr y BFEEvents
S
BFEResultAuth Información del resultado del proceso de autorización S
BFEErr Información del error producido (0 – OK ) S
BFEEvents Información de eventos programados (mantenimiento, etc)
(0 – OK )
S
BFEResultAuth : el resultado del proceso del pedido de autorización tiene los siguientes
campos:

Campo Tipo Detalle Obligatorio
Id long Identificador del requerimiento S
Cuit long Cuit del contribuyente S
Cae string CAE S
Fch_venc_Cae string Fecha de vencimiento del CAE S

Fch_cbte String Fecha de comprobante S

Resultado string Resultado S
Reproceso string Indica si es un reproceso “S” o “N” S
Obs string Observaciones, motivo de rechazo según tabla de moti-
vos.

Para Facturas de Crédito las validaciones especificas
son:
15 LA CUIT INFORMADA DEL EMISOR NO
CUMPLE LAS CONDICIONES SEGÚN EL
RÉGIMEN FCE
16 LA CUIT INFORMADA DEL EMISOR NO
TIENE ACTIVO EL DOMICILIO FISCAL
ELECTRONICO
17 SI EL TIPO DE COMPROBANTE QUE ES-
TÁ AUTORIZANDO ES MIPYMES (FCE), EL
RECEPTOR DEL COMPROBANTE INFOR-
MADO EN DOCTIPO Y DOCNRO DEBE CO-
RRESPONDER A UN CONTRIBUYENTE CA-
RACTERIZADO COMO GRANDE O PYME
QUE OPTÓ.
18 SI EL TIPO DE COMPROBANTE QUE ES-
TÁ AUTORIZANDO ES MIPYMES (FCE), EL
RECEPTOR DEL COMPROBANTE DEBE TE-
NER HABILITADO EL DOMICILIO FISCAL
ELECTRÓNICO
22 SI EL TIPO DE COMPROBANTE QUE
ESTÁ AUTORIZANDO ES MIPYMES (FCE)
CLASE "B", EL RECEPTOR DEL COMPRO-
BANTE INFORMADO EN DOCTIPO Y DOCN-
RO DEBE ENCONTRARSE REGISTRADOS
DE FORMA ACTIVA EN EL IMPUESTO IVA,
MONOTIBUTO o EXENTO.
Para todos los comprobantes donde el receptor se en-
cuentra identificado como APOCRIFO, salimos con la
sig. observación:
21 LA CUIT RECEPTORA SE ENCUENTRA
INACTIVA POR HABER SIDO INCLUÍDA EN
LA CONSULTA DE FACTURAS APÓCRIFAS.
NO PODRÁ COMPUTARSE EL CRÉDITO
FISCAL
Para los comprobantes del tipo A:
23 DETECTAMOS QUE TENES PENDIENTE
DE PRESENTACIÓN EL FORMULARIO DE
HABILITACIÓN DE COMPROBANTES, O SU
FECHA DE PRESENTACIÓN ES ANTERIOR
A TU ALTA EN IVA. Para el caso de
facturas/Notas de Débito, tenés que proceder
a anular la operación emitida, mediante una
Nota de Crédito.
S
Para todos los comprobantes:
24 LA CUIT RECEPTORA QUE INGRESAS-
TE NO EXISTE. Para el caso de facturas/No-
tas de Débito, tenés que emitir una Nota de
Crédito o anular la operación, según corres-
ponda.
Para comprobantes Notas de Crédito:
25 El importe de la nota de crédito supera el
monto del comprobante asociado que estás
ajustando. Verificá los montos ingresados y de
tratarse de un error, tenés que efectuar el
ajuste o anulación de la operación según co-
rresponda.
Para Condición IVA receptor :
26 El campo Condición Frente al IVA del re-
ceptor resultara obligatorio conforme lo regla-
mentado por la Resolución General Nro 5616.
Para mas información consular método BFE-
GetPARAM_CondicionIvaReceptor.
2.1.4 VALIDACIONES DE ESTRUCTURA Y ERRORES..................................................................................
Los siguientes controles se realizan en el WS.

Descripción de la validación
Código de
error
Mensaje de error
Tipo de dato y longitud de cada
campo

1014
2.1.5 VALIDACIONES DE CABECERA Y ERRORES......................................................................................
Los siguientes controles se realizan en el WS.

Descripción de la validación
Código de
error
Mensaje de error
Verificación de Token y Firma (^1000) Usuario no autorizado a realizar esta
operación
Cuit solicitante se encuentra entre sus
representados
1001 Cuit solicitante no se encuentra entre sus
representados
Identificador del requerimiento sea
mayor que 0.

1014
2.1.6 VALIDACIONES DE NEGOCIO Y ERRORES........................................................................................
Los siguientes controles se realizan en el WS.

Descripción de la validación
Código de
error Mensaje de error
Campo punto_vta se encuentre
entre 1 y 99998 y que sea único para
el requerimiento.

1014
Campo tipo_cbte sea: 1014 Tipo de comprobante inválido.

Campo cbte_nro esté entre 1 y 1014

99999999.
Para comprobantes clase A el campo
tipo_doc tenga valor 80 (CUIT)

1014 El tipo de documento debe ser igual a 80
(CUIT) en comprobantes tipo A.
El campo fecha_cbte (yyyymmdd) pue-
de ser hasta 5 días
anteriores o posteriores respecto de la
fecha de generación. La misma no po-
drá exceder el mes de presentación.

Si no se envía la fecha del comproban-
te se asignará la
fecha de proceso

(^1014) No es una fecha valida. Debe ser numérico
de 8 con formato (yyyymmdd).
No podrá exceder el mes de la fecha de
envío del pedido de autorización.
La fecha debe estar incluida en el periodo
+- 5 días de la fecha de presentación.
IMPORTE DE OPERACIONES
EXENTAS
IMPORTE DE PERCEPCIONES O
PAGOS A CUENTA DE IMPUESTOS
NACIONALES
IMPORTE DE PERCEPCION DE
INGRESOS BRUTOS
IMPORTE DE PERCEPCION DE
IMPUESTOS MUNICIPALES
IMPORTE DE IMPUESTOS
INTERNOS
sean menores o iguales al
IMPORTE TOTAL DE LA OPERACIÓN
/ IMPORTE TOTAL POR LOTE
(^1014) Se valida que la suma de importes de los
ítems sea menor igual a los importes
totales del comprobante.

IMPORTE DE OPERACIONES
EXENTAS
1014 Se valida que el importe de operaciones
exentas sea mayor a 0 en los casos donde
exista alguna 17 ítem de factura con Iva
exento
<Imp_moneda_Id>/<Imp_moneda_ctz> 1014 El tipo de cambio no podrá ser inferior al
2% ni superior en un 400% del que
suministra ARCA como orientativo de
acuerdo a la cotización oficial.

2.1.7 OTROS ERRORES..........................................................................................................................
Los mensajes de error que aún no están contemplados salen por código 1014 incluyendo un texto
que explica la causa exacta del error.

Código de error Mensaje de error
1014 Valor inválido en campo (a este código se le agregará una descripción
detallada del origen del error (nombre de campo y causa) )
1015 Opcionales ->Opcional : de informar <Opcionales> debe informar de
forma completa la estructura <Opcionales><Opcional><Id>
1016 El valor ingresado en <Id> debe ser alguno permitido. Consultar método
BFEGetPARAM_Tipo_Opc.
1017 El campo <Id> en <Opcionales> es obligatorio y no debe repetirse.
1018 El campo <Valor> en Opcionales es obligatorio
1019 <Opcionales><Id><Valor>. Si selecciona Id = 2 el valor ingresado debe
ser un numérico de 8 (ocho) dígitos mayor o igual a 0 (cero).
1020 Si Id = 2 y el comprobante corresponde a una actividad alcanzada por
el beneficio de Promoción Industrial en el campo <Valor> se deberá
informar el número identificatorio del proyecto (el mismo deberá
corresponder a la cuit emisora del comprobante), si no corresponde a
una actividad alcanzada por el beneficio el campo <Valor> deberá ser 0
(cero).
1030 Si envía CbtesAsoc, CbteAsoc es obligatorio y no puede estar vacío
1031 De enviarse el tag CbteAsoc debe enviarse mayor a
0

1032 De enviarse el tag CbteAsoc debe enviarse mayor a 0 y menor a 99998.

1033 De enviarse el tag CbteAsoc debe enviarse > a 0 y <
a 99999999.

1034 De enviarse el tag CbteAsoc, los comprobantes no deben repetirse.

1035 De enviarse el tag , entonces el campo tipo de compro-
bante <Tipo_cbte> a autorizar tiene que ser 01, 02, 03, 06, 07,
08, 91, 201, 202, 203, 206, 207, 208

1036 Para <Tipo_cbte>01 o 06 solo puede asociarse el tipo de
comprobante <Tipo_cbte> 91.

1037 Para <Tipo_cbte> 02 o 03 pueden asociarse los tipos de
comprobante <Tipo_cbte> 01, 02, 03, 91.

1038 Para <Tipo_cbte> 07 u 08 pueden asociarse los tipos de
comprobante <Tipo_cbte> 06, 07, 08, 91.

1039 Si el punto de venta del comprobante asociado (CbtesAsoc.Punto_vta)
es electrónico y del tipo Bonos, el número de comprobante debe obrar
en las bases del organismo para el punto de venta y tipo de
comprobante informado.

4880 Si el punto de venta del comprobante asociado (campo < Punto_vta >
de ) es electrónico, el número de comprobante debe obrar
en las bases del organismo para el punto de venta y tipo de
comprobante informado.

4881 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE) y
corresponde a un comprobante de débito o crédito, tener en cuenta
que:

sí y el comprobante asociado se encuentra rechazado por el
comprador hay que informar el código de anulación correspondiente
sobre el campo "Adicionales por RG", códigos 22 - Anulación. Valor “S”
(<Tipo_cbte><Punto_vta><Cbte_nro>)
sí y el comprobante asociado se encuentra aceptado por el comprador
hay que informar el código de no anulación correspondiente sobre el
campo "Adicionales por RG", códigos 22 - Anulación. Valor “N”
(<Tipo_cbte><Punto_vta><Cbte_nro>)
4882 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), Dé-
bito o Crédito, el comprobante debe existir autorizado en las bases de
esta Administración con la misma fecha informada en el asociado (<Cb-
teAsoc><Tipo_cbte><Punto_vta><Cbte_nro><Fecha_cbte>)
4883 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), es
débito o crédito, deben coincidir emisores y receptores.
4884 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), y
es crédito y coinciden emisores y receptores el monto del comprobante
a autorizar no puede ser mayor o igual al saldo actual de la cuenta co-
rriente. Ver micro sitio factura de crédito
4886 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE) y
corresponde a un comprobante de débito o crédito, es obligatorio infor-
mar comprobantes asociados. (<Tipo_cbte>/
/)
4887 Si informa Cuit en comprobantes asociados, no informar en blanco, el
mismo debe ser un valor de 11 caracteres numéricos.
Para comprobante del tipo MiPyMEs (FCE) del tipo débito o crédito es
obligatorio informar el campo (campo )
4888 De enviarse el tag , para <Tipo_cbte> comprobantes MiP-
yMEs (FCE) 201 o 206, solo puede asociarse comprobante 91.
4889 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), Dé-
bito o Crédito tipo A sin código de Anulación, siempre debe asociar 1 y
solo 1 comprobante tipo factura A (201). No puede haber dos o más
comprobantes tipo factura A (201) asociados a un comprobante a auto-
rizar.

4890 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), Dé-
bito o Crédito tipo A, sin código de Anulación, solo puede asociar:
Para comprobantes A, asociar 201 o 91 (<Tipo-
_cbte >/ )
4891 Para comprobante de anulación, campo CbtesAsoc con tipo invalido.
Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), Dé-
bito o Crédito tipo A y el comprobante es de anulación, el campo Cbtes-
Asoc debe contener uno de los siguientes valores: 201, 202, 203
( /<Tipo_cbte>). De forma complementaria se puede aso-
ciar el código 91.
4892 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), Dé-
bito o Crédito tipo B sin código de Anulación, siempre debe asociar 1 y
solo 1 comprobante tipo factura B (206). No puede haber dos o más
comprobantes tipo factura B (206) asociados a un comprobante a auto-
rizar. De forma complementaria se puede asociar el código 91.
4893 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), Dé-
bito o Crédito tipo B, sin código de Anulación, solo puede asociar:
Para comprobantes B, asociar 206 (<Tipo_cbte

/ ). De forma complemen-
taria se puede asociar el código 91.
4894 Para comprobante de anulación, campo CbtesAsoc con tipo invalido.
Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), Dé-
bito o Crédito tipo B y el comprobante es de anulación, el campo Cbtes-
Asoc debe contener uno de los siguientes valores: 206, 207, 208.
/ <Tipo_cbte>
4895 De informar el campo Fecha del Comprobante Asociado <Fecha_cbte>,
la fecha del comprobante asociado tiene que ser igual o menor a la fe-
cha del comprobante que se está autorizando
4896 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), Dé-
bito o Crédito, es obligatorio informar la fecha del comprobante asocia-
do
4897 Para comprobantes MiPyMEs (FCE), el campo <Fecha_cbte> podrá es-
tar comprendido en el rango N-5 y N+1 siendo N la fecha de envío del
pedido de autorización. (<Cbte_nro>/<Fecha_cbte>//<Fecha_-
cbte>)
4898 Si informa fecha de comprobante <Fecha_cbte> para comprobante del
tipo MiPyMEs (FCE) con fecha superior a la fecha de envío de autoriza-
ción, el mes de la fecha del comprobante <Fecha_cbte> debe coincidir
con el mes de la fecha de envío de autorización.
4900 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE),
Tipo 201 - FACTURA DE CREDITO ELECTRONICA MiPyMEs (FCE)
A / 206 - FACTURA DE CREDITO ELECTRONICA MiPyMEs (FCE) B ,
es obligatorio informar el campo Fecha_vto_pago (<Fecha_vto_pago>)
4901 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), la
fecha de vencimiento de pago <Fecha_vto_pago> debe ser posterior o
igual a la fecha de emisión (CbteFch) o fecha de presentación (fecha
actual), la que sea posterior (<Fecha_vto_pago>)
4902 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), el
campo “fecha de vencimiento para el pago” <Fecha_vto_pago> no debe
informarse si NO es Factura de Crédito (Cbte_tipo 201 / 206). En el
caso de ser Nota de Débito o Crédito, solo puede informarse si es de
Anulación. (<Fecha_vto_pago>)
4903 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE),
Nota Débito o Nota Crédito, la moneda del comprobante a autorizar
debe ser igual a la moneda del comprobante asociado o Pesos para
ajuste en las diferencias de cambio (post aceptación/rechazo).
4905 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE),
Tipo 201 - FACTURA DE CREDITO ELECTRONICA MiPyMEs (FCE)
A / 206 - FACTURA DE CREDITO ELECTRONICA MiPyMEs (FCE) B,
es obligatorio informar
4906 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), in-
forma opcionales, el valor correcto para el código 2101 es un CBU nu-
mérico de 22 caracteres. ()
4907 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), in-

forma opcionales, el valor correcto para el código 2102 es un ALIAS al-
fanumérico de 6 a 20 caracteres. ()
4908 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), in-
forma opcionales, el valor correcto para el código 22 es “S” o “N”:
S = Es de Anulación
N = No es de Anulación

4909 Opcionales. No informar identificadores de resoluciones distintas en un
mismo comprobante.
4910 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), es
obligatorio informar al menos uno de los siguientes códigos 2101, 2102,
22, 27.
()
4911 Si el tipo de comprobante que está autorizando es Factura (201, 206)
del tipo MiPyMEs (FCE), informa opcionales, es obligatorio informar
CBU.
()
4912 Si el tipo de comprobante que está autorizando es MiPyMEs

(FCE), Factura (201 o 206), no informar Código de Anulación.
()
4913 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), in-
forma CBU o Alias, el mismo debe estar registrado en las bases de esta
administración, vigente y pertenecer al emisor del comprobante.
()
4914 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), De-
bito (202, 207) o Crédito (203, 208) No informar CBU o ALIAS. Solo se
permite informar Código de Anulación.
()
4915 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), De-
bito (202, 207) o Crédito (203, 208) informar Código de Anulación.
()
4916 Si el tipo de comprobante que está autorizando NO es MiPyMEs (FCE),
no informar los códigos 2101, 2102, 22, 27.
()
4917 Si informa el campo CbtesAsoc, el Cuit debe informarlo como numérico
de 11 caracteres.
4918 Para comprobantes MIPYMEs (FECRED), tipo cmp. N. Débito o N. Cré-
dito A de ANULACION, no se puede informar más de una ND/NC A
como Cmp. Asociado (202/203)
.
4919 Para comprobantes MIPYMEs (FECRED), tipo Nota de Debito A (202)
de ANULACION, no se puede informar Factura A (201) ni Nota de Debi-
to A (202) como CMP. Asociado

4920 Para comprobantes MIPYMEs (FECRED), tipo Nota de Credito A (203)
de ANULACION, no se puede informar una Nota de Credito A (203)
como CMP. Asociado

4921 Para comprobantes MIPYMEs (FECRED), tipo cmp. N. Débito o N. Cre-
dito B de ANULACION, no se puede informar más de una ND/NC B
como Cmp. Asociado (207/208)
4922 Para comprobantes MIPYMEs (FECRED), tipo Nota de Debito B (207)
de ANULACION, no se puede informar Factura B (206) ni Nota de Debi-
to B (207) como CMP. Asociado
4923 Para comprobantes MIPYMEs (FECRED), tipo Nota de Credito B (208)
de ANULACION, no se puede informar una Nota de Credito B (208)
como CMP. Asociado
4924 Para comprobantes MIPYMEs (FECRED), tipo Nota de Debito/Credito
A (202/203) No de ANULACION, debe informarse una y solo una Factu-
ra A (201) obligatoriamente.
4925 Para comprobantes MIPYMEs (FECRED), tipo Nota de Debito/Credito
B (207/208) No de ANULACION, debe informarse una y solo una Factu-
ra B (206) obligatoriamente.

4926 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), es
crédito el monto del comprobante a autorizar no puede ser mayor o
igual al saldo actual de la cuenta corriente. Ver micrositio factura de cré-
dito
4927 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), es
crédito o débito, el comprobante asociado debe estar aceptado o recha-
zado por el sistema de gestión de créditos. Ver micrositio factura de
crédito
4928 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), es
crédito o débito A, de anulación, solo se encuentra habilitado asociar un
comprobante de crédito A.
4929 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), es
crédito o débito B, de anulación, solo se encuentra habilitado asociar un
comprobante de crédito B.
4931 Puede identificar una o varias Referencias Comerciales según corres-
ponda. Informar bajo el código 23. Campo alfanumérico de 50 caracte-
res como máximo.
4932 Si informa opcionales con más de un identificador 23 – Referencia Co-
mercial, no repetir el valor.
4933 El importe Total de la Factura de Crédito (código tipo comprobante 201,

debe ser mayor o igual al tope establecido para emisión de factura
de crédito. Ver micrositio.
4944 Según la categorización de las CUITs emisora y receptora y el monto
facturado debe realizar una factura de crédito electrónica MiPyMEs
(FCE). Ver micrositio.
4945 El comprobante electrónico asociado se encuentra autorizado pero los
receptores no coinciden. Si está autorizando un comprobante MiPyMEs
(FCE), el receptor del comprobante asociado debe ser el mismo que el
receptor del comprobante que está intentando autorizar.
4946 Para Notas de Debito / Crédito del tipo MiPMEs (FCE), es obligatorio in-
formar Opcionales - Marca de Anulación (S/N). Ver método FEParam-
GetTiposOpcional() para mayor información.
4947 Comprobante asociado no existe en los registros del organismo.
4948 Para comprobante MiPyMEs (FCE), del tipo Nota de Crédito, el monto
del comprobante a autorizar no puede ser mayor o igual al saldo actual
de la cuenta corriente.
4949 Comprobante electrónico asociado autorizado. No es válida la marca de
anulación para el estado actual del comprobante.
4950 Tipo de comprobante asociado invalido para comprobantes MiPyMEs
(FCE) de anulación.

Para comprobante MiPyMEs (FCE), del tipo Nota de Debito A con Anu-
lación, solo informar 1 comprobante Asociado 203.
Para comprobante MiPyMEs (FCE), del tipo Nota de Crédito A con Anu-
lación, solo informar 1 comprobante Asociado 201 o 202.
4951 Tipo de comprobante asociado invalido para comprobantes MiPyMEs
(FCE) de anulación.

Para comprobante MiPyMEs (FCE), del tipo Nota de Debito B con Anu-
lación, solo informar 1 comprobante Asociado 208.
Para comprobante MiPyMEs (FCE), del tipo Nota de Crédito B con Anu-
lación, solo informar 1 comprobante Asociado 206 o 207.
4952 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE), in-
forma opcionales, el tipo de dato correcto para el código 27 es un alfa-
numérico de 3 caracteres.
4953 Si el tipo de comprobante que está autorizando es MiPyMEs (FCE),
informa opcionales y el código es 27, los valores posibles son:

SCA = "TRANSFERENCIA AL SISTEMA DE CIRCULACION ABIERTA"
ADC = "AGENTE DE DEPOSITO COLECTIVO"
4954 Si el tipo de comprobante que está autorizando es Factura del tipo

MiPyMEs (201, 206, 211), es obligatorio informar <Opcionales> con id
= 27. Los valores posibles son SCA o ADC.
4955 Si el tipo de comprobante que está autorizando es Factura del tipo
MiPyMEs (201, 202, 203, 206, 207, 208), el campo <Cmp>.<Imp_total>
(Importe total de la operación) deber ser igual o mayor a 0 (cero).
4956 Al momento de autorizar un comprobante del tipo débito o crédito, al
asociar sus comprobantes, tener en cuenta que los puntos de venta
deben pertenecer al mismo régimen de facturación.
4957 El campo Imp_moneda_ctz es obligatorio si no informa el campo
CanMisMonExt con valor S o si la moneda del comprobante no tiene
cotización en Banco Nación o el comprobante no es del tipo factura. El
mismo debe ser mayor a 0.
4958 Si informa Imp_moneda_Id = PES, el campo CanMisMonExt NO debe
informarse (o informarse con el valor N)
4959 Si informa el campo CanMisMonExt, los valores posibles son S o N y no
debe quedar vacío.
4960 Si informa el campo Imp_moneda_ctz, el mismo no podra superar en 1
a la cotización oficial. Ver Método BFEGetCotizacion.
4961 El campo Condición IVA receptor no es un valor permitido. Consular
método BFEGetPARAM_CondicionIvaReceptor.
4962 El campo Condición IVA receptor no es valido para la clase de
comprobante informado.
Consular método BFEGetPARAM_CondicionIvaReceptor.
4963 Campo Condición Frente al IVA del receptor es obligatorio conforme a
lo reglamentado por la Resolución General N° 5616. Para mas
información consular método BFEGetPARAM_CondicionIvaReceptor
VALIDACIONES NO EXCLUYENTES:.............................................................................................................
4930 Si el tipo de comprobante que está autorizando es 1 – Factura A o 6 -
Factura B, por la categorización de las cuits emisora y receptora, se de-
bería realizar una factura de crédito electrónica.
2.2 RECUPERADOR DE COMPROBANTE (BFEGETCMP)..........................................................................
2.2.1 DIRECCIÓN URL...........................................................................................................................
Este servicio se llama desde:

http://wswhomo.afip.gov.ar/wsbfev1/service.asmx?op=BFEGetCMP

2.2.2 MENSAJE DE SOLICITUD................................................................................................................
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetCMP xmlns="http://ar.gov.afip.dif.bfev1/">
<Auth>
<Token> string </Token>
<Sign> string </Sign>
<Cuit> long </Cuit>
</Auth>
<Cmp>
<Tipo_cbte> short </Tipo_cbte>
<Punto_vta> int </Punto_vta>
<Cbte_nro> long </Cbte_nro>
</Cmp>
</BFEGetCMP>
</soap:Body>
</soap:Envelope>
2.2.3 MENSAJE DE RESPUESTA..............................................................................................................
Retorna los detalles de un comprobante ya enviado y autorizado.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetCMPResponse xmlns="http://ar.gov.afip.dif.bfev1/">
<BFEGetCMPResult>
<BFEResultGet>
<Id> long </Id>
<Cuit> long </Cuit>
<Tipo_doc> short </Tipo_doc>
<Nro_doc> long </Nro_doc>
<Tipo_cbte> short </Tipo_cbte>
<Punto_vta> int </Punto_vta>
<Cbte_nro> long </Cbte_nro>
<Imp_total> double </Imp_total>
<Imp_tot_conc> double </Imp_tot_conc>
<Imp_neto> double </Imp_neto>
<Impto_liq> double </Impto_liq>
<Impto_liq_rni> double </Impto_liq_rni>
<Imp_op_ex> double </Imp_op_ex>
<Imp_perc> double </Imp_perc>
<Imp_iibb> double </Imp_iibb>
<Imp_perc_mun> double </Imp_perc_mun>
<Imp_internos> double </Imp_internos>
<Imp_moneda_Id> string </Imp_moneda_Id>
<Imp_moneda_ctz> double </Imp_moneda_ctz>
<Fecha_cbte_orig> string </Fecha_cbte_orig>
<Fecha_cbte_cae> string </Fecha_cbte_cae>
<Fecha_vto_pago> string </Fecha_vto_pago>
<Cae> string </Cae>
<Resultado> string </Resultado>
<Obs> string </Obs>
<CondicionIVAReceptorId> int </CondicionIVAReceptorId>
<CanMisMonExt> string </CanMisMonExt>
<Opcionales>
<Opcional xsi:nil="true" />
</Opcionales>
<Items>
<Item xsi:nil="true" />
</Items>
<CbtesAsoc>
<CbteAsoc>
<Tipo_cbte> short </Tipo_cbte>
<Punto_vta> int </Punto_vta>
<Cbte_nro> long </Cbte_nro>
<Cuit> string </Cuit>
<Fecha_cbte> string </Fecha_cbte>
</CbteAsoc>
<CbteAsoc>
</CbtesAsoc>
</BFEResultGet>
<BFEErr>
<ErrCode> int </ErrCode>
<Errmsg> string </Errmsg>
</BFEErr>
<BFEEvents>
<EventCode> int </EventCode>
<EventMsg> string </EventMsg>
</BFEEvents>
</BFEGetCMPResult>
</BFEGetCMPResponse>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Detalle Obligatorio
BFEResultGet Información completa del comprobante autorizado S
Ítems Información de los ítems que componen el documento S
BFEResultGet : La cabecera del comprobante está compuesta por los siguientes campos:

Campo Tipo Detalle Obligatorio
Tipo_doc int Código de documento del comprador S
Nro_doc long Nro. de identificación del comprador S
Zona Short Código de zona S
tipo_cbte int Tipo de comprobante (ver anexo A) S
Punto_vta int Punto de venta S
Cbt_nro long Nro. de comprobante S
Imp_total double Importe total de la operación S
Imp_tot_conc double Importe total de conceptos que no integran el
precio neto gravado
S
Imp_neto double Importe neto gravado S
Impto_liq double Importe liquidado S
Impto_liq_rni double Impuesto liquidado a RNI o percepción a no
categorizados
S
imp_op_ex double Importe de operaciones exentas S
Imp_perc double Importe de percepciones S
Imp_internos double Importe de impuestos internos S
Imp_moneda_Id double Código de moneda(ver anexo A) S
Imp_moneda_ctz double Cotización de moneda S
Fecha_cbte_orig string Fecha de comprobante ingreso (yyyymmdd) N
Fecha_cbte_cae string Fecha de comprobante otorgado en caso de
omitirla en la presentación (yyyymmdd)
S
Fecha_vto_pago String Fecha de vencimiento de pago. Solo para
Factura de Crédito MiPyme (yyyymmdd)
N
Fecha_cae string Fecha de autorización (yyyymmdd) S
CondicionIVARe-
ceptorId
int Condicion frente al IVA del receptor N
CanMisMonExt String Marca que identifica si el comprobante se
cancela en misma moneda del comprobante
(moneda extranjera). Valores posibles S o N.
N
Items Item Detalle de ítem S
Opcionales Opcional Detalle de opcionales N
CbtesAsoc CbteAsoc Detalle de Comprobantes Asociados N
Opcionales : sección para informar campos opcionales:

Campo Tipo Detalle Obligatorio
Id String Código del tipo de opcional. S
Valor String Valor a registrado. S
Items : el detalle de los ítems del comprobante está compuesto por los siguientes campos:

Campo Tipo Detalle Obligatorio
Pro_codigo_ncm string Código de producto (nomenclador común del
MERCOSUR)
S
Pro_codigo_sec String Código de producto según Secretaria N
Pro_ds String Descripción del producto S
Pro_qty Double Cantidad S
Pro_umed int Código de unidad de medida S
Pro_precio_uni Double Precio unitario S
Imp_bonif double Importe bonificación S
Imp_total Double Importe total S
Iva_id Int Código de IVA S
2.2.4 ERRORES.....................................................................................................................................
Código de error Mensaje de error
1020 Comprobante inexistente
2.3 RECUPERADOR DE VALORES REFERENCIALES DE CÓDIGOS DE MONEDA (BFEGETPARAM_MON)....
2.3.1 DIRECCIÓN URL...........................................................................................................................
Este servicio se llama desde:

http://wswhomo.afip.gov.ar/wsbfev1/service.asmx?op= BFEGetPARAM_MON

2.3.2 MENSAJE DE SOLICITUD................................................................................................................
Recibe las credenciales de autenticación y la cuit del usuario representado.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetPARAM_MON xmlns="http://ar.gov.afip.dif.bfev1/">
<Auth>
<Token> string </Token>
<Sign> string </Sign>
<Cuit> long </Cuit>
</Auth>
</BFEGetPARAM_MON>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Detalle Obligatorio
Auth Información de la autenticación. Contiene los datos
de Token, Sign , Cuit
S
Token Token devuelto por el WSAA S
Sign Sign devuelto por el WSAA S
Cuit Cuit contribuyente (representado o Emisora) S
2.3.3 MENSAJE DE RESPUESTA..............................................................................................................
Retorna el total de monedas válidas.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetPARAM_MONResponse xmlns="http://ar.gov.afip.dif.bfev1/">
<BFEGetPARAM_MONResult>
<BFEResultGet>
<ClsBFEResponse_Mon>
<Mon_Id> string </Mon_Id>
<Mon_Ds> string </Mon_Ds>
<Mon_vig_desde> string </Mon_vig_desde>
<Mon_vig_hasta> string </Mon_vig_hasta>
</ClsBFEResponse_Mon>
<ClsBFEResponse_Mon>
<Mon_Id> string </Mon_Id>
<Mon_Ds> string </Mon_Ds>
<Mon_vig_desde> string </Mon_vig_desde>
<Mon_vig_hasta> string </Mon_vig_hasta>
</ClsBFEResponse_Mon>
</BFEResultGet>
<BFEErr>
<ErrCode> int </ErrCode>
<Errmsg> string </Errmsg>
</BFEErr>
<BFEEvents>
<EventCode> int </EventCode>
<EventMsg> string </EventMsg>
</BFEEvents>
</BFEGetPARAM_MONResult>
</BFEGetPARAM_MONResponse>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Tipo Detalle Obligatorio
Mon_id String Código de moneda S
Mon_ds String Descripción de moneda S
Mon_vig_desde String Fecha de vigencia desde S
Mon_vig_hasta String Fecha de vigencia hasta N
2.3.4 VALIDACIONES, ACCIONES Y ERRORES...........................................................................................
Este servicio devuelve el siguiente código de error:

Descripción de la validación Código de^
error
Mensaje de error
Verificación de Token y Firma (^1000) Usuario no autorizado a realizar esta
operación
Cuit solicitante se encuentra entre sus
representados
1001 Cuit solicitante no se encuentra entre sus
representados

2.4 RECUPERADOR DE VALORES REFERENCIALES DE CÓDIGOS DE PRODUCTOS (BFEGETPARAM_NCM)
2.4.1 DIRECCIÓN URL...........................................................................................................................
Este servicio se llama desde:

http://wswhomo.afip.gov.ar/wsbfev1/service.asmx?op=BFEGetPARAM_NCM

2.4.2 MENSAJE DE SOLICITUD................................................................................................................
Retorna el total de productos aceptados.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetPARAM_NCM xmlns="http://ar.gov.afip.dif.bfev1/">
<Auth>
<Token> string </Token>
<Sign> string </Sign>
<Cuit> string </Cuit>
</Auth>
</BFEGetPARAM_NCM>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Detalle Obligatorio
Auth Información de la autenticación. Contiene los datos
de Token, Sign , Cuit
S
Token Token devuelto por el WSAA S
Sign Sign devuelto por el WSAA S
Cuit Cuit contribuyente (representado o Emisora) S
2.4.3 MENSAJE DE RESPUESTA..............................................................................................................
Retorna el listado completo de código de productos autorizados.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetPARAM_NCMResponse xmlns="http://ar.gov.afip.dif.bfev1/">
<BFEGetPARAM_NCMResult>
<BFEResultGet>
<ClsBFEResponse_NCM>
<NCM_Codigo> string </NCM_Codigo>
<NCM_Ds> string </NCM_Ds>
<NCM_Nota> string </NCM_Nota>
<NCM_vig_desde> string </NCM_vig_desde>
<NCM_vig_hasta> string </NCM_vig_hasta>
</ClsBFEResponse_NCM>
<ClsBFEResponse_NCM>
<NCM_Codigo> string </NCM_Codigo>
<NCM_Ds> string </NCM_Ds>
<NCM_Nota> string </NCM_Nota>
<NCM_vig_desde> string </NCM_vig_desde>
<NCM_vig_hasta> string </NCM_vig_hasta>
</ClsBFEResponse_NCM>
</BFEResultGet>
<BFEErr>
<errcode> int </errcode>
<errmsg> string </errmsg>
</BFEErr>
<BFEEvents>
<eventcode> int </eventcode>
<eventmsg> string </eventmsg>
</BFEEvents>
</BFEGetPARAM_NCMResult>
</BFEGetPARAM_NCMResponse>
</soap:Body> </soap:Envelope>
Dónde:

Campo Tipo Detalle Obligatorio
Ncm_codigo String Código de producto S
Ncm_Ds String Descripción de producto S
Ncm_Nota String Nota S
Ncm_vig_desde String Fecha de vigencia desde S
Ncm_vig_hasta String Fecha de vigencia hasta N
2.4.4 VALIDACIONES, ACCIONES Y ERRORES...........................................................................................
Este servicio devuelve el siguiente código de error:

Descripción de la validación Código de^
error
Mensaje de error
Verificación de Token y Firma (^1000) Usuario no autorizado a realizar esta
operación
Cuit solicitante se encuentra entre sus
representados
1001 Cuit solicitante no se encuentra entre sus
representados

(BFEGETPARAM_TIPO_CBTE)................................................................................................................. 2.5 RECUPERADOR DE VALORES REFERENCIALES DE CÓDIGOS DE TIPOS DE COMPROBANTE
(BFEGetPARAM_Tipo_cbte)
2.5.1 DIRECCIÓN URL...........................................................................................................................
Este servicio se llama desde:

http://wswhomo.afip.gov.ar/wsbfev1/service.asmx?op= BFEGetPARAM_Tipo_cbte

2.5.2 MENSAJE DE SOLICITUD................................................................................................................
Recibe las credenciales de autenticación y la cuit del usuario representado.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetPARAM_Tipo_Cbte xmlns="http://ar.gov.afip.dif.bfev1/">
<Auth>
<Token> string </Token>
<Sign> string </Sign>
<Cuit> string </Cuit>
</Auth>
</BFEGetPARAM_Tipo_Cbte>
</soap:Body> </soap:Envelope>
Dónde:

Campo Detalle Obligatorio
Auth Información de la autenticación. Contiene los datos
de Token, Sign , Cuit
S
Token Token devuelto por el WSAA S
Sign Sign devuelto por el WSAA S
Cuit Cuit contribuyente (representado o Emisora) S
2.5.3 MENSAJE DE RESPUESTA..............................................................................................................
Retorna el universo de tipos de comprobante válidos.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetPARAM_Tipo_CbteResponse
xmlns="http://ar.gov.afip.dif.bfev1/">
<BFEGetPARAM_Tipo_CbteResult>
<BFEResultGet>
<ClsBFEResponse_Tipo_Cbte>
<Cbte_Id> short </Cbte_Id>
<Cbte_Ds> string </Cbte_Ds>
<Cbte_vig_desde> string </Cbte_vig_desde>
<Cbte_vig_hasta> string </Cbte_vig_hasta>
</ClsBFEResponse_Tipo_Cbte>
<ClsBFEResponse_Tipo_Cbte>
<Cbte_Id> short </Cbte_Id>
<Cbte_Ds> string </Cbte_Ds>
<Cbte_vig_desde> string </Cbte_vig_desde>
<Cbte_vig_hasta> string </Cbte_vig_hasta>
</ClsBFEResponse_Tipo_Cbte>
</BFEResultGet>
<BFEErr>
<ErrCode> int </ErrCode>
<Errmsg> string </Errmsg>
</BFEErr>
<BFEEvents>
<EventCode> int </EventCode>
<EventMsg> string </EventMsg>
</BFEEvents>
</BFEGetPARAM_Tipo_CbteResult>
</BFEGetPARAM_Tipo_CbteResponse>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Tipo Detalle Obligatorio
Cbte_id Short Código de comprobante S
Cbte_ds String Descripción S
Cbte_vig_desde String Fecha de vigencia desde S
Cbte_vig_hasta String Fecha de vigencia hasta N
2.5.4 VALIDACIONES, ACCIONES Y ERRORES...........................................................................................
Este servicio devuelve el siguiente código de error:

Descripción de la validación
Código de
error
Mensaje de error
Verificación de Token y Firma (^1000) Usuario no autorizado a realizar esta
operación
Cuit solicitante se encuentra entre sus
representados
1001 Cuit solicitante no se encuentra entre sus
representados

(BFEGETPARAM_TIPO_IVA).................................................................................................................... 2.6 RECUPERADOR DE VALORES REFERENCIALES DE CÓDIGOS ALÍCUOTAS DE IVA
(BFEGetPARAM_Tipo_iva)
2.6.1 DIRECCIÓN URL...........................................................................................................................
Este servicio se llama desde:

http://wswhomo.afip.gov.ar/wsbfev1/service.asmx?op= BFEGetPARAM_Tipo_iva

2.6.2 MENSAJE DE SOLICITUD................................................................................................................
Recibe las credenciales de autenticación y la cuit del usuario representado.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetPARAM_Tipo_Iva xmlns="http://ar.gov.afip.dif.bfev1/">
<Auth>
<Token> string </Token>
<Sign> string </Sign>
<Cuit> string </Cuit>
</Auth>
</BFEGetPARAM_Tipo_Iva>
</soap:Body> </soap:Envelope>
Dónde:

Campo Detalle Obligatorio
Auth Información de la autenticación. Contiene los datos
de Token, Sign , Cuit
S
Token Token devuelto por el WSAA S
Sign Sign devuelto por el WSAA S
Cuit Cuit contribuyente (representado o Emisora) S
2.6.3 MENSAJE DE RESPUESTA..............................................................................................................
Retorna el universo de tipos de comprobante válidos.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetPARAM_Tipo_CbteResponse
xmlns="http://ar.gov.afip.dif.bfev1/">
<BFEGetPARAM_Tipo_CbteResult>
<BFEResultGet>
<ClsBFEResponse_Tipo_Iva>
<Iva_Id> string </Iva_Id>
<Iva_Ds> string </Iva_Ds>
<Iva_vig_desde> string </Iva_vig_desde>
<Iva_vig_hasta> string </Iva_vig_hasta>
</ClsBFEResponse_Tipo_Iva>
<ClsBFEResponse_Tipo_Iva>
<Iva_Id> string </Iva_Id>
<Iva_Ds> string </Iva_Ds>
<Iva_vig_desde> string </Iva_vig_desde>
<Iva_vig_hasta> string </Iva_vig_hasta>
</ClsBFEResponse_Tipo_Iva>
</BFEResultGet>
<BFEErr>
<errcode> int </errcode>
<errmsg> string </errmsg>
</BFEErr>
<BFEEvents>
<eventcode> int </eventcode>
<eventmsg> string </eventmsg>
</BFEEvents>
</BFEGetPARAM_Tipo_IvaResult>
</BFEGetPARAM_Tipo_IvaResponse>
</soap:Body> </soap:Envelope>
Dónde:

Campo Tipo Detalle Obligatorio
Iva_id Short Código de IVA S
IVA_ds String Descripción S
IVA_vig_desde String Fecha de vigencia desde S
IVA_vig_hasta String Fecha de vigencia hasta N
2.6.4 VALIDACIONES, ACCIONES Y ERRORES...........................................................................................
Este servicio devuelve el siguiente código de error:

Descripción de la validación
Código de
error Mensaje de error
Verificación de Token y Firma (^1000) Usuario no autorizado a realizar esta
operación
Cuit solicitante se encuentra entre sus
representados
1001 Cuit solicitante no se encuentra entre sus
representados

2.7 RECUPERADOR DE VALORES REFERENCIALES DE CÓDIGOS DE ZONA (BFEGETPARAM_ZONAS).......
2.7.1 DIRECCIÓN URL...........................................................................................................................
Este servicio se llama desde:

http://wswhomo.afip.gov.ar/wsbfev1/service.asmx?op= BFEGetPARAM_Zonas

2.7.2 MENSAJE DE SOLICITUD................................................................................................................
Recibe las credenciales de autenticación y la cuit del usuario representado.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetPARAM_Zonas xmlns="http://ar.gov.afip.dif.bfev1/">
<auth>
<Token> string </Token>
<Sign> string </Sign>
<Cuit> string </Cuit>
</Auth>
</BFEGetPARAM_Zonas>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Detalle Obligatorio
Auth Información de la autenticación. Contiene los datos
de Token, Sign , Cuit e Id
S
Token Token devuelto por el WSAA S
Sign (^) Sign devuelto por el WSAA S
Cuit Cuit contribuyente (representado o Emisora) S

2.7.3 MENSAJE DE RESPUESTA..............................................................................................................
Retorna el total de zonas válidas.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetPARAM_ZonasResponse
xmlns="http://ar.gov.afip.dif.bfev1/">
<BFEGetPARAM_ZonasResult>
<BFEResultGet>
<ClsBFEResponse_Zon>
<Zon_Id> string </Zon_Id>
<Zon_Ds> string </Zon_Ds>
<Zon_vig_desde> string </Zon_vig_desde>
<Zon_vig_hasta> string </Zon_vig_hasta>
</ClsBFEResponse_Zon>
<ClsBFEResponse_Zon>
<Zon_Id> string </Zon_Id>
<Zon_Ds> string </Zon_Ds>
<Zon_vig_desde> string </Zon_vig_desde>
<Zon_vig_hasta> string </Zon_vig_hasta>
</ClsBFEResponse_Zon>
</BFEResultGet>
<BFEErr>
<errcode> int </errcode>
<errmsg> string </errmsg>
</BFEErr>
<BFEEvents>
<eventcode> int </eventcode>
<eventmsg> string </eventmsg>
</BFEEvents>
</BFEGetPARAM_ZonasResult>
</BFEGetPARAM_ZonasResponse>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Tipo Detalle Obligatorio
Zon_id Int Código de zona S
Zon_ds String Descripción de zona S
Zon_vig_desde String Fecha de vigencia desde S
Zon_vig_hasta String Fecha de vigencia hasta N
2.7.4 VALIDACIONES, ACCIONES Y ERRORES...........................................................................................
Este servicio devuelve el siguiente código de error:

Descripción de la validación Código de^
error
Mensaje de error
Verificación de Token y Firma (^1000) Usuario no autorizado a realizar esta
operación
Cuit solicitante se encuentra entre sus
representados
1001 Cuit solicitante no se encuentra entre sus
representados

(BFEGETPARAM_TIPO_OPC)................................................................................................................. 2.8 RECUPERADOR DE VALORES REFERENCIALES DE CÓDIGOS DE OPCIONALES
(BFEGetPARAM_Tipo_Opc)
2.8.1 DIRECCIÓN URL...........................................................................................................................
Este servicio se llama desde:

http://wswhomo.afip.gov.ar/wsbfev1/service.asmx?op= BFEGetPARAM_Tipo_Opc

2.8.2 MENSAJE DE SOLICITUD................................................................................................................
Recibe las credenciales de autenticación y la cuit del usuario representado.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetPARAM_Tipo_Opc xmlns="http://ar.gov.afip.dif.bfev1/">
<auth>
<Token> string </Token>
<Sign> string </Sign>
<Cuit> long </Cuit>
</auth>
</BFEGetPARAM_Tipo_Opc>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Detalle Obligatorio
Auth Información de la autenticación. Contiene los datos
de Token, Sign , Cuit e Id
S
Token Token devuelto por el WSAA S
Sign (^) Sign devuelto por el WSAA S
Cuit Cuit contribuyente (representado o Emisora) S

2.8.3 MENSAJE DE RESPUESTA..............................................................................................................
Retorna el total de opcionales válidos.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetPARAM_Tipo_OpcResponse
xmlns="http://ar.gov.afip.dif.bfe/">
<BFEGetPARAM_Tipo_OpcResult>
<BFEResultGet>
<ClsBFEResponse_Opc>
<Opc_Id> short </Opc_Id>
<Opc_Ds> string </Opc_Ds>
<Opc_vig_desde> string </Opc_vig_desde>
<Opc_vig_hasta> string </Opc_vig_hasta>
</ClsBFEResponse_Opc>
<ClsBFEResponse_Opc>
<Opc_Id> short </Opc_Id>
<Opc_Ds> string </Opc_Ds>
<Opc_vig_desde> string </Opc_vig_desde>
<Opc_vig_hasta> string </Opc_vig_hasta>
</ClsBFEResponse_Opc>
</BFEResultGet>
<BFEErr>
<ErrCode> int </ErrCode>
<ErrMsg> string </ErrMsg>
</BFEErr>
<BFEEvents>
<EventCode> int </EventCode>
<EventMsg> string </EventMsg>
</BFEEvents>
</BFEGetPARAM_Tipo_OpcResult>
</BFEGetPARAM_Tipo_OpcResponse>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Tipo Detalle Obligatorio
Opc_id String Código de “opcional” S
Opc_ds String Descripcion S
Opc_vig_desde String Fecha de vigencia desde S
Opc_vig_hasta String Fecha de vigencia hasta N
2.8.4 VALIDACIONES, ACCIONES Y ERRORES...........................................................................................
Este servicio devuelve el siguiente código de error:

Descripción de la validación
Código de
error
Mensaje de error
Verificación de Token y Firma (^1000) Usuario no autorizado a realizar esta
operación
Cuit solicitante se encuentra entre sus
representados
1001 Cuit solicitante no se encuentra entre sus
representados

(BFEGETPARAM_CONDICIONIVARECEPTOR)........................................................................................... 2.9 RECUPERADOR DE VALORES REFERENCIALES DE CÓDIGOS DE CONDICIÓN DE IVA DEL RECEPTOR
receptor (BFEGetPARAM_CondicionIvaReceptor)
2.9.1 DIRECCIÓN URL...........................................................................................................................
Este servicio se llama desde:

http://wswhomo.afip.gov.ar/wsbfev1/service.asmx?op=
BFEGetPARAM_CondicionIvaReceptor

2.9.2 MENSAJE DE SOLICITUD................................................................................................................
Recibe las credenciales de autenticación y la cuit del usuario representado.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetPARAM_CondicionIvaReceptor
xmlns="http://ar.gov.afip.dif.bfev1/">
<Auth>
<Token> string </Token>
<Sign> string </Sign>
<Cuit> long </Cuit>
</Auth>
<ClaseCmp> string </ClaseCmp>
</BFEGetPARAM_CondicionIvaReceptor>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Detalle Obligatorio
Auth Información de la autenticación. Contiene los datos
de Token, Sign , Cuit
S
Token Token devuelto por el WSAA S
Sign (^) Sign devuelto por el WSAA S
Cuit Cuit contribuyente (representado o Emisora) S
ClaseCmp Clase de comprobante. Valor opcional. De informarlo
debe ser A o B o C

N
2.9.3 MENSAJE DE RESPUESTA..............................................................................................................
Retorna el listado de identificadores de IVA del receptor.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetPARAM_CondicionIvaReceptorResponse
xmlns="http://ar.gov.afip.dif.bfev1/">
<BFEGetPARAM_CondicionIvaReceptorResult>
<BFEResultGet>
<ClsBFEResponse_CondicionIvaReceptor>
<Id> int </Id>
<Desc> string </Desc>
<Cmp_Clase> string </Cmp_Clase>
</ClsBFEResponse_CondicionIvaReceptor>
<ClsBFEResponse_CondicionIvaReceptor>
<Id> int </Id>
<Desc> string </Desc>
<Cmp_Clase> string </Cmp_Clase>
</ClsBFEResponse_CondicionIvaReceptor>
</BFEResultGet>
<BFEErr>
<ErrCode> int </ErrCode>
<Errmsg> string </Errmsg>
</BFEErr>
<BFEEvents>
<EventCode> int </EventCode>
<EventMsg> string </EventMsg>
</BFEEvents>
</BFEGetPARAM_CondicionIvaReceptorResult>
</BFEGetPARAM_CondicionIvaReceptorResponse>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Tipo Detalle Obligatorio
Id Short(N2) Código de IVA S
Desc String(C250) Descripción S
Cmp_Clase String(C1) Clase de Comprobante S
2.9.4 VALIDACIONES, ACCIONES Y ERRORES...........................................................................................
Este servicio devuelve el siguiente código de error:

Descripción de la validación Código de^
error
Mensaje de error
Verificación de Token y Firma (^1000) Usuario no autorizado a realizar esta
operación
Cuit solicitante se encuentra entre sus
representados
1001 Cuit solicitante no se encuentra entre sus
representados
Validacion de Cmp_Clase 4967 El valor ingresado para la clase de
comprobante no es valido. La clase de
Comprobante es opcional, de ingresar un
valor solo puede ser A o B

2.10 RECUPERADOR DE COTIZACIONES REGISTRADAS EN EL ORGANISMO (BFEGETCOTIZACION)............
2.10.1 DIRECCIÓN URL.........................................................................................................................
Este servicio se llama desde:

http://wswhomo.afip.gov.ar/wsbfev1/service.asmx?op= BFEGetCotizacion

2.10.2 MENSAJE DE SOLICITUD..............................................................................................................
Recibe las credenciales de autenticación y la cuit del usuario representado. Aparte debe
indicar la moneda y fecha (opcional) que desea consultar.

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetCotizacion xmlns="http://ar.gov.afip.dif.bfev1/">
<Auth>
<Token> string </Token>
<Sign> string </Sign>
<Cuit> long </Cuit>
</Auth>
<MonId> string <MonId>
<FchCotiz> string </FchCotiz>
</BFEGetPARAM_CondicionIvaReceptor>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Detalle Obligatorio
Auth Información de la autenticación. Contiene los datos
de Token, Sign , Cuit
S
Token Token devuelto por el WSAA S
Sign (^) Sign devuelto por el WSAA S
Cuit Cuit contribuyente (representado o Emisora) S
MonId Identificador de la moneda por la cual desea consul-
tar la cotización. Para ver detalle de las monedas
permitidas consultar el método BFEGetPARAM_MON.

S
FchCotiz Fecha a la cual desea consultar la cotización registra-
da en el organismo. El formato de la fecha a consul-
tar debe ser YYYYMMDD (donde YYYY corresponde
al año, MM al mes y DD al día de la fecha a consul-
tar). Este campo es opcional, de no informarse, se to-
mara la fecha del día como default.
N
2.10.3 MENSAJE DE RESPUESTA............................................................................................................
Retorna el valor de la cotización de la moneda consultada a la fecha indicada (si no
especifico fecha se tomara la del día actual).

<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-
instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<BFEGetCotizacionResponse
xmlns="http://ar.gov.afip.dif.bfev1/">
<BFEGetCotizacionResult>
<BFEResultGet>
<MonId> string </MonId>
<MonCotiz> double </MonCotiz>
<FchCotiz> string </FchCotiz>
</BFEResultGet>
<BFEErr>
<ErrCode>0</ErrCode>
<ErrMsg>OK</ErrMsg>
</BFEErr>
</BFEGetCotizacionResult>
</BFEGetCotizacionResponse>
</soap:Body>
</soap:Envelope>
Dónde:

Campo Tipo Detalle Obligatorio
MonId
String
Identificador de la moneda por la
cual desea consultar la
cotización. Para ver detalle de
las monedas permitidas
consultar el método
BFEGetPARAM_MON.
S
MonCotiz Double Valor de la cotización registrada
en el organismo.
S
FchCotiz String(C8) Fecha a la cual fue consultada la
cotización de la moneda
S
2.10.4 VALIDACIONES, ACCIONES Y ERRORES.........................................................................................
Este servicio devuelve el siguiente código de error:

Descripción de la validación Código de^
error
Mensaje de error
Verificación de Token y Firma 1000 Usuario no autorizado a realizar esta
operación

Cuit solicitante se encuentra entre sus
representados

1001 Cuit solicitante no se encuentra entre sus
representados
Sin resultados 4964 Sin Resultados. A la fecha consultada no se
registran valores de cotización para la
moneda indicada

Identificador de moneda invalido 4965 El identificador de moneda (MonId)
ingresado es invalido. Este campo es
obligatorio y no puede quedar vacío.
Verificar los códigos mediante el metodo

Descripción de la validación Código de^
error
Mensaje de error
BFEGetPARAM_MON.
Fecha de cotización invalido 4966 Campo FchCotiz no corresponde a una
fecha valida con formato YYYYMMDD. Este
campo es opcional, de informarlo la fecha
debe tener el formato YYYYMMDD donde
YYYY corresponde al año, MM al mes y DD
al día solicitado. De no informarlo se tomara
la fecha del día actual como valor por
default.

3 ANEXOS...........................................................................................................................................
3.1 CONDICIÓN FRENTE AL IVA DEL RECEPTOR.........................................................................................
CONDICION FRENTE AL IVA
Código Descripción
COMPROBANTES CLASE
A B
(^1) IVA Responsable Inscripto X
(^4) IVA Sujeto Exento X
(^5) Consumidor Final X
(^6) Responsable Monotributo X
(^7) Sujeto No Categorizado X
(^8) Proveedor del Exterior X
(^9) Cliente del Exterior X
(^10) IVA Liberado – Ley N° 19.640 X
(^13) Monotributista Social X
(^15) IVA No Alcanzado X

16
Monotributo Trabajador
Independiente Promovido X
This is a offline tool, your data stays locally and is not send to any server!
Feedback & Bug Reports