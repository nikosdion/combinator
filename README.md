# JavaScript and CSS combination for Joomla

Combine JavaScript and CSS on your Joomla site

## Installation

You can download an installable ZIP file for the plugin from the releases page of this repository. Alternatively, if you want to use the latest available code, create a ZIP file with the contents of the `plugins/system/combinator` folder of this repository. In either case, install it in Joomla like any other extension.

Now you need to activate the plugin:

* Go to Extensions, Manage, Plugins.
* Filter by plugins in the `system` folder.
* Order the display of plugins by ordering, ascending.
* Move the “System – Combinator” plugin all the way to the bottom. **THIS IS VERY IMPORTANT**. This plugin must be the very last system plugin to execute on your site!
* Finally, enable the “System – Combinator” plugin.

Remember to edit the plugin options. You will need to enter a list of the CSS and JS files to combine.

## Configuration

### A word of caution

Combining JS and CSS is not straightforward. It takes a certain amount of trial and error to find which files and in which order can be reliably combined in one big file.

Almost certainly your first attempt will result in your site breaking. You will need to start backtracking on the number and order of files you are combining until you find something that works.

If you are not ready to spend a lot of time with trial and error, expect things to magically work with no effort or don't feel comfortable with the fact that you _will_ break your site in the process you shouldn't use this plugin.  
This is not to say that this plugin isn't good. To the contrary, it's a high quality _tool_. It's a sharp and sturdy chisel with a well-balanced hammer. It makes it possible to create something beautiful, but you still need to put the time and skill towards that end. 

### Basic Configuration

You need to edit the plugin parameters and tell it which files it should combine _and in which order_.

Each file is given as a path relative to your site's root with a leading slash. For example, if your site is installed on `https://www.example.com/joomla` and you want to include the file `https://www.example.com/joomla/media/jui/js/jquery.min.js` you need to enter `/media/jui/js/jquery.min.js`.

Remember that files need to be an exact match. This typically boils down to two common mistakes:
* Case matters. The files `/media/jui/js/jquery.min.js` and `/media/jui/js/jQuery.min.js` are **different**. Typing the latter will NOT cause the former to be combined.
* Extensions matter. The files `/media/jui/js/jquery.min.js` and `/media/jui/js/jquery.js` are **different**. Typing the latter will NOT cause the former to be combined.

You can optionally specify a _tag_ for each file. One combined file will be generated per tag. This is a feature for very advanced uses. If you're not sure if you need this feature leave the Output File Tag field blank. 

Combined files are included _before_ any other script or stylesheet. They are loaded in the same order this plugin encounters the tags you have declared. The default tag (empty) is always loaded first, even if you have a file with another tag before the first file with no tag.

As a rule of thumb, start by looking at the source ccode of each page of your site with this plugin disabled. You will see which CSS and JS files are loaded and in which order. You need to transcribe these files and in this order to the plugin.

Do not include JS files (`<script>` tags) with the `defer` or `async` attribute. These files are loaded without blocking your browser whereas combined files are loaded in a blocking manner. That is to say, if you try to combine these files you will hurt your site's performance more than if you didn't. There's no such consideration for CSS files since they do not support asynchronous or deferred loading.

Your JS and CSS files may be included using either an absolute URL or a URL relative to your domain root. You need to convert it to a URL relative to your site's root when transcribing them to the plugin. Moreover, you must NOT include the media version queries.

**IMPORTANT** I strongly recommend AGAINST trying to include files put in place by WYSIWYG editors such as Joomla's TinyMCE or JCE. 

Also note that some files are placed in the HTML output by plugins modifying the HTML source directly instead of going through Joomla's API for CSS/JS management. There files cannot be combined by this plugin. So you might put some file in the plugin and it will _still_ not disappear from the HTML output, i.e. it's not combined. At least you know why. 

#### Example 1. Site hosted on the domain root

Your site is hosted in `https://www.example.com` and you see the following code in its HTML output:
```html
<script type="text/javascript" src="https://www.example.com/media/com_example/js/foo.js" defer></script>
<script type="text/javascript" src="https://www.example.com/media/com_example/js/bar.js?1234567890abcdef1234567890abcdef"></script>
<script type="text/javascript" src="/media/com_example/js/baz.js?1234567890abcdef1234567890abcdef"></script>
```

In this case you need to add TWO files to the plugin from the second and third line:

* `media/com_example/js/bar.js`
* `media/com_example/js/baz.js`

Here's why, line by line.

The first line contains a defered script. As said above, you need to ignore it.

The second line has an absolute URL with a media query: `https://www.example.com/media/com_example/js/bar.js?1234567890abcdef1234567890abcdef`. First, we toss the media query to get the absolute URL `https://www.example.com/media/com_example/js/bar.js`. Then we remove the absolute URL to our site (`https://www.example.com`) and we get `/media/com_example/js/bar.js`.

The third line has a URL relative to your site's domain root with a media query: `/media/com_example/js/baz.js?1234567890abcdef1234567890abcdef`. First we toss the media query and we get `/media/com_example/js/baz.js`. Your site is hosted in the domain root, therefore a URL relative to the domain root is also a URL relative to the site. So we just keep the URL `/media/com_example/js/baz.js`.

#### Example 2. Site hosted in a subdirectory

Your site is hosted in `https://www.example.com/hello` and you see the following code in its HTML output:
```html
<script type="text/javascript" src="https://www.example.com/hello/media/com_example/js/foo.js" defer></script>
<script type="text/javascript" src="https://www.example.com/hello/media/com_example/js/bar.js?1234567890abcdef1234567890abcdef"></script>
<script type="text/javascript" src="/hello/media/com_example/js/baz.js?1234567890abcdef1234567890abcdef"></script>
```

In this case you need to add TWO files to the plugin from the second and third line:

* `media/com_example/js/bar.js`
* `media/com_example/js/baz.js`

Here's why, line by line.

The first line contains a defered script. As said above, you need to ignore it.

The second line has an absolute URL with a media query: `https://www.example.com/hello/media/com_example/js/bar.js?1234567890abcdef1234567890abcdef`. First, we toss the media query to get the absolute URL `https://www.example.com/hello/media/com_example/js/bar.js`. Then we remove the absolute URL to our site (`https://www.example.com/hello`) and we get `/media/com_example/js/bar.js`.

The third line has a URL relative to your site's domain root with a media query: `/hello/media/com_example/js/baz.js?1234567890abcdef1234567890abcdef`. First we toss the media query and we get `/hello/media/com_example/js/baz.js`. Your site is hosted in the subdirectory `/hello`. So now we need to remove that from the beginning of the URL to make it relative to our site. Therefore we get `/media/com_example/js/baz.js`.

### How it works and advanced configuration

The plugin installs a plugin event handler which is triggered when Joomla is compiling the header of the HTML document.

The event handler looks for JS and CSS files known to Joomla which match what you have defined in the plugin configuration. Media queries are ignored.

Note that minified and non-minified files are equally caught by the plugin, no matter which one you specified. For example, if you used the file `/media/com_example/js/foobar.js` the plugin will combine all of the following files if they are known to Joomla:
* `/media/com_example/js/foobar.js` (what you entered)
* `/media/com_example/js/foobar.min.js` (minified file)
* `/media/com_example/js/foobar-uncompressed.js` (old-style non-minified file)

All files of the same type files are concatenated into one big file inside the respective `media/plg_system_combinator` subfolder. For example, all JS files are concatenated into one file inside `media/plg_system_combinator/js`. 

The name of the file is an md5 hash derived from the content of the files being combined, the version of Combinator you are using, your Joomla version and your site's secret key. That is to say, each page with a unique combination of files will have a different combined file generated inside `media/plg_system_combinator`. If the contents of a file included in the combined file is updated, or if Combinator is updated, or if Joomla is updated: you get a differently named file.

Depending on your plugin options the combined file may be minified. This is controlled by the “Minify combined JavaScript” and “Minify combined CSS” options. Minification takes place through [a third party PHP library](http://www.minifier.org). 

Depending on your options, JavaScript minification may take place using Google's Closure Compiler – it's slower but results in much smaller files. If you want to use Closure Compiler enter the full path to its executable in the “Closure Compiler command” option. This does NOT work on Windows. Also note that Closure Compiler will increase the page load time every time a file is being minified by several seconds.

Also depending on your options, both the combined and the minified files can be compressed. This is controlled with the “Compress combined JavaScript” and “Compress combined CSS” options. First, Combinator will try to compress the files with GZip into a `.gz` file, if your PHP version supports GZip compression (this is a requirement for running Joomla so the answer is that probably yes, it does). If your server has the [PHP Brotli extension](https://github.com/kjdev/php-ext-brotli) these files will also be compressed with Brotli into the respective `.br` file. Compressed files are not used automatically. They require a change in your .htaccess files; see the "Using compressed files" section below.

Finally, the plugin will remove any references to the files it combined from Joomla's document object, replacing them with a reference to the combined or minified (if configured) file.

Do note that combined, minified and compressed files will only be generated if the respective file does not already exist. The only exception to this rule is when you have set Debug Site to Yes in your Global Configuration and have enabled the “Always regenerate when Debug Site is enabled” option in your plugin configuration. If you want to force the regeneration of the combined, minified or compressed files you need to empty the `media/plg_system_combinator/css` and `media/plg_system_combinator/js` folders.

### The Magic Key option

If you want to remove all cached files (combined, minfied, compressed) you normally need to delete the contents of the `media/plg_system_combinator/css` and `media/plg_system_combinator/js` folders on your site.

Alternatively, you can set up a Magic Key in the plugin and visit the URL `https://www.example.com/index.php?option=com_ajax&group=system&plugin=combinator&format=raw&&akaction=purgemagic=MAGIC_KEY` where `https://www.example.com` is the URL to your site and `MAGIC_KEY` is the Magic Key you configured. You should do this every time you update your site or your extensions to avoid ending up with many "junk" files.

### Using compressed files

You can tell Apache to automatically used the pre-compressed .gz (GZip) and .br (Brotli) files generated by the plugin. This results in a reduced size for over-the-wire transferred content without adversely affecting the performance of your web server, if the web browser supports these compressed content encodings – most modern browsers do.

You need the following .htaccess code:

```apacheconfig
<IfModule mod_headers.c>
    # Serve Brotli compressed CSS files if they exist and the client accepts Brotli.
    RewriteCond "%{HTTP:Accept-encoding}" "br"
    RewriteCond "%{REQUEST_FILENAME}\.gz" -s
    RewriteRule "^(.*)\.css" "$1\.css\.br" [QSA]

    # Serve Brotli compressed JS files if they exist and the client accepts Brotli.
    RewriteCond "%{HTTP:Accept-encoding}" "br"
    RewriteCond "%{REQUEST_FILENAME}\.gz" -s
    RewriteRule "^(.*)\.js" "$1\.js\.br" [QSA]
    
    # Serve correct content types, and prevent double compression.
    RewriteRule "\.css\.br$" "-" [E=no-gzip:1]
    RewriteRule "\.css\.br$" "-" [T=text/css,E=no-brotli:1,L]
    RewriteRule "\.js\.br$" "-" [E=no-gzip:1]
    RewriteRule "\.js\.br$"  "-" [T=text/javascript,E=no-brotli:1,L]
    
    <FilesMatch "(\.js\.br|\.css\.br)$">
      # Serve correct encoding type.
      Header append Content-Encoding br

      # Force proxies to cache gzipped & non-gzipped css/js files separately.
      Header append Vary Accept-Encoding
    </FilesMatch>

    # Serve gzip compressed CSS files if they exist and the client accepts gzip.
    RewriteCond "%{HTTP:Accept-encoding}" "gzip"
    RewriteCond "%{REQUEST_FILENAME}\.gz" -s
    RewriteRule "^(.*)\.css" "$1\.css\.gz" [QSA]

    # Serve gzip compressed JS files if they exist and the client accepts gzip.
    RewriteCond "%{HTTP:Accept-encoding}" "gzip"
    RewriteCond "%{REQUEST_FILENAME}\.gz" -s
    RewriteRule "^(.*)\.js" "$1\.js\.gz" [QSA]

    # Serve correct content types, and prevent double compression.
    RewriteRule "\.css\.gz$" "-" [E=no-brotli:1]
    RewriteRule "\.css\.gz$" "-" [T=text/css,E=no-gzip:1,L]
    RewriteRule "\.js\.gz$" "-" [E=no-brotli:1]
    RewriteRule "\.js\.gz$" "-" [T=text/javascript,E=no-gzip:1,L]

    <FilesMatch "(\.js\.gz|\.css\.gz)$">
      # Serve correct encoding type.
      Header append Content-Encoding gzip

      # Force proxies to cache gzipped & non-gzipped css/js files separately.
      Header append Vary Accept-Encoding
    </FilesMatch>
</IfModule>
```  

I do not know if there's an equivalent for NginX or IIS. At least, I haven't found any. So please don't ask me about non-Apache servers. Thank you!

### Considerations about backing up your site

You should exclude the `media/plg_system_combinator/css` and `media/plg_system_combinator/js` folders from your backup. These folders contain generated (cached) files. These files don't need to be backed up.

The reason these files are in a `media` folder subdirectory instead of the `cache` directory is that they need to be web accessible. Remember that the `cache` folder of your site is supposed to be inaccessible from the web and recommended to be placed _outside_ your site's web root. Web accessible generated or cached content is supposed to be under the `media` folder.

## Things you should know before you use this plugin

Let me start this off by saying the obvious. Combining CSS and/or JS files will very likely break your site. Minifying them make it even more likely. This plugin is made available on the assumption that you are a responsible adult, someone who understands how sites work, is willing to accept the risk of breaking things and understands how to fix them. This software's license explicitly states there is no warranty and has a liability waiver; if you mess up your site using this plugin you get the blame. On the bright side, disabling the plugin (and clearing Joomla's and your browser's cache) will fix your site. If you do not agree with these terms you are not allowed to use the software.

Remember that your Joomla site outputs code which is the combined effort of many different developers who mostly work without coordinating with each other. That's why each extension loads its own CSS and JS and in a way that is the least likely to cause problems with other extensions. Combining the CSS / JS files is actively changing the _very reasonable_ assumptions third party developers made about how Joomla works. As a result, it will very likely cause display issues and/or JavaScript errors. It will take a lot of work on your part to come up with the optimal selection and order of files to combine for the site to work properly with a minimum amount of requests made to the server. This process typically takes hours for a simple, small site and days or weeks for a more complex site. It also requires frequent maintenance e.g. when you update Joomla or your extensions as things _may_ break again. If you are not prepared to put in the required time and effort – **STOP NOW**, uninstall this plugin and be content with how your site works. 
 
Don't be unrealistically optimistic about combining every JS and CSS file under the sun (you'll end up with a broken site at best). Add files carefully and progressively. Some files cannot be combined. Always err on the side of caution. Be careful and methodical. Test thoroughly after every small change. Remember that haste is the enemy of quality. If you have a looming deadline maybe don't try to gain the marginal benefits of combining files and focus on shipping a working site instead.

Speaking of which, combining CSS and JS files is the very dead last thing you should do on a site. Premature optimization is not only a waste of time, it's going to introduce hard to debug issues. If something isn't working properly stop and think. Can you reproduce the problem after disabling this plugin? If not, your problem lies with the combination of your site's CSS and JS files, i.e. you screwed up the order of what you are combining or you're trying to combine files which shouldn't be combined. Backtrack to the last known good settings and start working on the site methodically. 

This plugin will _only_ combine the JS and CSS files which are added to Joomla through its document object before the template asks it to compile its head element (through the API or with a jdoc tag). Files which are hardcoded in the template's output, added by manipulating the HTML document or included through JavaScript will NOT be combined. Trying to do so is a very precarious proposition, one I am not interested in pursuing.

The combined files MUST exist on your server and MUST be readable by PHP. Just the fact that a file is served through your web server and your browser can read it IS NOT an indication that _either_ of these conditions is true. If you do not understand the difference between a file being served through a web server and a file being readable through PHP you do not possess the minimum knowledge required to start doing advanced optimization of your site, i.e. you shouldn't be using this plugin just yet. Try to jump the learning curve at your own risk and peril.

A corollary of the above is that dynamically generated scripts / stylesheets can not be combined. If it has `index.php` in its name or otherwise looks like it's going through Joomla or a `.php` script: it cannot be combined. 

A secondary corollary of the above is that using a third party extension, script or template feature which combines, minifies or compresses (GZips) JavaScript and / or CSS files is incompatible with this plugin. Disable all such solutions before trying to use this plugin. Please keep in mind that compressing files in PHP is more than 10x slower than letting your server do that.

Order matters. The files are included in the combined file in the order you declare them in the configuration. Maintaining the correct order of dependencies is your responsibility. If a third party script depends on inline JavaScript executing before it you will not be able to combine it; it'd break your site. Same for CSS depending on inline CSS.