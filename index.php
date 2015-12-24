<!DOCTYPE HTML>
<html>
   <head>
      <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
      <title>ASH</title>
      <link rel="stylesheet" type="text/css" href="style.css">
      <script type='text/javascript' src='jquery/jquery-2.1.4.min.js'></script>
      <script type='text/javascript' src='highcharts/highcharts.js'></script>
      <script type='text/javascript' src='highcharts/legend-highlight.js'></script>
      <script type='text/javascript' src='highcharts/highcharts-default-settings.js'></script>
      <script type='text/javascript' src='myash.js'></script>
   </head>
   <body style='font-size:12px;font-family: Tahoma,Verdana,Helvetica,sans-serif;'>
      <table width='100%' border=0>
         <tr style='vertical-align:top' align='left' >
            <td>
               <table>
                  <tr>
                     <td align='right' nowrap >
                        Host: <input type='text' id='host' value='127.0.0.1' size='10' />
                     </td>
                     <td nowrap>
                        Port: <input type='text' id='port' value='1521' size='10'/>
                     </td>
                     <td nowrap>
                        Service name: <input type='text' id='service' value='orcl' size='10'/>
                     </td>
                     <td align='right' nowrap>
                        Username: <input type='text' id='username' value='system' size='10'/>
                     </td>
                     <td nowrap>
                        Password: <input type='password' id='password' value='123456' size='10'/>
                     </td>
                     <td nowrap>
                        <button type='button' id='connect'>Connect</button>
                     </td>
                  </tr>
               </table>

            </td>
         </tr>
         <tr>
            <td style='vertical-align:middle' align='middle' height='40px'>
               <div id='instance-name' style='font-size:14px;'>&nbsp;</div>
            </td>
         </tr>
         <tr>
            <td style='vertical-align:middle;visibility:hidden' align='right' id='awr-dates'>
               <button id='top-activity' style='visibility:hidden'>Top activity</button>
               <input type='checkbox' align='right' id='historical' style='vertical-align:middle;bottom:1px;position:relative'/>Historical
               &nbsp;&nbsp;<select id='dbid' disabled></select>
               &nbsp;&nbsp;<select id='day' disabled></select>
            </td>
         </tr>
      </table>

      <div id='container' style='min-width: 310px; height: 300px; margin: 0 auto'></div>

      <table align='center'>
         <tr>
            <tr>
               <td style='text-align:center' colspan=2>
                  <div id='selected-interval'> </div><br/>
               </td>
            </tr>
            <td valign='top'>
               <div id='top-sql'></div>
            </td>
            <td valign='top'>
               <div id='top-session' style='margin-left:20px'></div>
            </td>
         </tr>
      </table>
   </body>
</html>
