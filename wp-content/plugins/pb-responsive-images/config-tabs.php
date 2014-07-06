<?php

$screen = get_current_screen();
$screen->add_help_tab(array(
   'id' => 'rip_usage',
   'title' => 'Usage',
   'content' => '<h3>PB Responsive Images General Usage Guidelines</h3>
         <p>PB Responsive Images works best with higher-resolution images. If you insert a high resolution image into your content,
         this plugin is best able to deliver the best results regardless of screen size. Provided, of course, that the plugin is
         configured correctly.</p>
         <p>This does not mean that lower resolution images will cause errors - but you will see no benefit from using responsive images
         in those cases.</p>
      '
));
$screen->add_help_tab(array(
   'id' => 'rip_help_media',
   'title' => 'Media Queries',
   'content' => '<h3>CSS Media Queries</h3>
         <p>The media field accepts any valid
         <a href="http://www.w3.org/TR/css3-mediaqueries/" target="_blank">CSS Media Query.</a></p>
         <p>As an example:</p>
         <pre>(min-width:420px) and (min-device-pixel-ratio:2)</pre>
         <p>The above query matches any retina display (2x pixel density) device with a minimum browser width of 420px.
         Additionally, the min-device-pixel-ratio query will automatically have vendor prefixes applied to it.</p>
      '
));
$screen->add_help_tab(array(
   'id' => 'rip_help_query',
   'title' => 'SLIR Query',
   'content' => '<h3>SLIR URL Queries</h3>
         <p>This responsive image plugin uses
         <a href="https://github.com/lencioni/SLIR" target="_blank">SLIR (Smart Lencioni Image Resizer)</a> to resize images.</p>
         <p>You can use the built in syntax for SLIR in the query field. For example,
         <strong>w400-q60</strong> will produce an image at most 400 pixels wide at 60% quality.</p>
         <p>A list of available parameters are listed below:</p>
         <table id="slir-options">
         <tbody><tr>
         <th>Parameter</th>
               <th>Meaning</th>
               <th>Example</th>
             </tr>
         <tr>
         <td><var>w</var></td>
               <td>Maximum width</td>
               <td>w100</td>
             </tr>
         <tr>
         <td><var>h</var></td>
               <td>Maximum height</td>
               <td>h100</td>
             </tr>
         <tr>
         <td><var>c</var></td>
               <td>Crop ratio</td>
               <td>c1x1</td>
             </tr>
         <tr>
         <td><var>q</var></td>
               <td>Quality</td>
               <td>q60</td>
             </tr>
         <tr>
         <td><var>b</var></td>
               <td>Background fill color</td>
               <td>bf00</td>
             </tr>
         <tr>
         <td><var>p</var></td>
               <td>Progressive</td>
               <td>p1</td>
             </tr>
         </tbody></table>
         <p>For additional information, see <a href="https://github.com/lencioni/SLIR" target="_blank">the SLIR project page</a>.</p>
      '
));
$screen->add_help_tab(array(
   'id' => 'rip_help_base_url',
   'title' => 'SLIR Base URL',
   'content' => '<h3>SLIR Base URL</h3>
         <p>In order to provide a shorter base URL for SLIR, this plugin attempts to modify your .htaccess file.
         If that fails, you can add the following to your .htaccess file (if supported)</p>
         <pre>RewriteRule ^slir(.*)$ /wp-content/plugins/pb-responsive-images/slir/index.php?r=$1 [L]</pre>
         <p>With this rule in place, your base url can then be set to <strong>{base-url}/slir/</strong></p>
         <h4>Bypassing mod_rewrite</h4>
         <p>If you\'re having issues with mod_rewrite or your .htaccess file, you can bypass them by setting your base url to
         <strong>{plugin-url}/slir/index.php?r=</strong></p>
      '
));
$screen->add_help_tab(array(
   'id' => 'rip_help_base_excluded',
   'title' => 'Exclude Images',
   'content' => '<h3>Exclude Images</h3>
         <p>To exclude an image from formatting, make sure it has the CSS class "non-responsive".</p>
      '
));
$screen->add_help_tab(array(
   'id' => 'rip_additional_functions',
   'title' => 'Additional Functions',
   'content' => '<h3>Additional Functions</h3>
         <p>There are a few other functions available for use in your themes as well</p>
         <ul>
            <li>RIP::get_picture($image,$formats);<br>
               <small>Get a picture for use in your theme. Accepts two arrays as arguments. Example:</small>
               <pre>
$image = array(
   \'src\' => get_header_image(),
   \'width\' => $header_image_width,
   \'height\' => $header_image_height,
   \'alt\' => \'\'
);

$formats = array(
   array("media"=>"" ,"query"=>"w368","fallback"=>true),
   array("media"=>"(min-device-pixel-ratio:2)" ,"query"=>"w736",),
   array("media"=>"(min-width:420px)" ,"query"=>"w833",),
   array("media"=>"(min-width:420px) and (min-device-pixel-ratio:2)" ,"query"=>"w1000",),
   array("media"=>"(min-width:885px)","query"=>"w1000"),
);

echo RIP::get_picture($image,$formats);
               </pre>
            </li>
            <li><small>Alternatively, you can pass in specific sources, if you wish to bypass SLIR image resizing. Example:</small>
               <pre>
$sources = array(
   array("media"=>"" ,"src"=>"/images/small.jpg","fallback"=>true),
   array("media"=>"(min-device-pixel-ratio:2)" ,"src"=>"/images/smallx2.jpg",),
   array("media"=>"(min-width:420px)" ,"src"=>"/images/medium.jpg",),
   array("media"=>"(min-width:420px) and (min-device-pixel-ratio:2)" ,"src"=>"/images/mediumx2.jpg",),
   array("media"=>"(min-width:885px)","src"=>"/images/large.jpg"),
);

$attributes = array(
   \'alt\' => \'Some Alt text\'
);

echo RIP::get_picture($sources,$attributes);
               </pre>
            </li>
            <li>RIP::set_options($options);<br>
               <small>Set options used when scrubbing the post content. Can be used to change formats per page or post.</small>
            </li>
            <li>RIP::reset_options();<br>
               <small>Reset to initially loaded settings.</small>
            </li>
         </ul>'
));
$screen->add_help_tab(array(
   'id' => 'rip_shortcodes',
   'title' => 'Shortcodes',
   'content' => '<h3>Shortcodes</h3>
         <p>You can have granular control on how to output your flexible images via the following shortcodes:</p>
         <ul>
            <li>The default<br>
               <small>This shortcode basicly parses as a standard image, and applies the default options set on this page. Example:</small>
               <pre>[RIPImage src="/images/an-image.png" alt="Alt text"]</pre>
            </li>
            <li>With Format Options<br>
               <small>You can set format options per image by using the RIPFormat shortcode. Example:</small>
               <pre>
[RIPImage src="/images/an-image.png" alt="Alt text" ]
   [RIPFormat media="all" query="w70" fallback="true"]
   [RIPFormat media="(max-width:200px)" query="w170"]
   [RIPFormat media="(max-width:300px)" query="w270"]
[/RIPImage]
               </pre>
            </li>
            <li>Fully Custom<br>
               <small>Finally, you can specify a custom image per media query with RIPSource shortcodes. Example:</small>
               <pre>
[RIPImage alt="Alt text" ]
   [RIPSource src="/images/an-image_1.png" media="all" fallback="true"]
   [RIPSource src="/images/an-image_2.png" media="(max-width:200px)"]
   [RIPSource src="/images/an-image_3.png" media="(max-width:300px)"]
[/RIPImage]
               </pre>
            </li>
         </ul>'
));
$screen->add_help_tab(array(
   'id' => 'rip_iis',
   'title' => 'Windows Hosting',
   'content' => '<h3>Windows Hosting</h3>
         <p>Under Windows hosting, you can still enable rewrites via the web.config file (IIS7 only):</p>
         <pre>'.
         htmlentities('<rule name="wordpress-slir" stopProcessing="true">
   <match url="^slir(.*)$" ignoreCase="false"/>
   <action type="Rewrite" url="/wp-content/plugins/pb-responsive-images/slir/index.php?r={R:1}" appendQueryString="false"/>
</rule>')
         .'</pre>
         <p>For IIS6, you can still use this plugin, you will just need to disable rewrites for this plugin. Paste the following into the
            base URL:</p>
         <pre>{plugin-url}/slir/index.php?r=</pre>'
));