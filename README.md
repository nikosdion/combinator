# JavaScript and CSS combination for Joomla

Combine JavaScript and CSS on your Joomla site

## Are you sure?

Combining JS and CSS is neither easy, nor straightforward. It's a project which will absorb significant amounts of time, both upfront and during the maintenance of your site. Are you _absolutely sure_ you want to continue down that path?

This is not to say that this plugin isn't good. To the contrary, it's a high quality _tool_. It's a sharp and sturdy chisel with a well-balanced hammer. It makes it possible to create something beautiful, but you still need to put the time and skill towards that end. 

## Instructions

Create a ZIP file with the contents of the `plugins/system/combinator` folder of this repository. Install it in Joomla like any other extension.

Go to Extensions, Manage, Plugins.
 
Filter by plugins in the `system` folder.

Move the “System – Combinator” plugin all the way to the top. Then, enable it.

Remember to edit the options. You will need to enter a list of the CSS and JS files to combine.

## Configuration

You need to edit the plugin parameters and tell it which files it should combine _and in which order_.

Each file is given as a path relative to your site's root with a leading slash. For example, if your site is installed on `https://www.example.com/joomla` and you want to include the file `https://www.example.com/joomla/media/jui/js/jquery.min.js` you need to enter `/media/jui/js/jquery.min.js`.

Remember that files need to be an exact match. This typically boils down to two common mistakes:
* Case matters. The files `/media/jui/js/jquery.min.js` and `/media/jui/js/jQuery.min.js` are **different**. Typing the latter will NOT cause the former to be combined.
* Extensions matter. The files `/media/jui/js/jquery.min.js` and `/media/jui/js/jquery.js` are **different**. Typing the latter will NOT cause the former to be combined.

You can optionally specify a _tag_ for each file. One combined file will be generated per tag. This is a feature for very advanced uses. If you're not sure if you need this feature leave the Output File Tag field blank. 

Combined files are included _before_ any other script or stylesheet. They are loaded in the same order this plugin encounters the tags you have declared. The default tag (empty) is always loaded first, even if you have a file with another tag before the first file with no tag.

## Admonitions (you'll wish you read this first)

Let me start this off by saying the obvious. Combining CSS and/or JS files will very likely break your site. This plugin is distributed on the assumption that you are a responsible adult, someone who understands how sites work, is willing to accept the risk of breaking things and understands how to fix them. This software's license explicitly states there is no warranty and has a liability waiver; if you mess up your site using this plugin you get the blame. If you do not agree with these terms you are not allowed to use the software.

Remember that your Joomla site outputs code which is the combined effort of many different developers who mostly work without coordinating with each other. That's why each extension loads its own CSS and JS and in a way that is the least likely to cause problems with other extensions. Combining the CSS / JS files is actively changing the _very reasonable_ assumptions third party developers made about how Joomla works. As a result, it will very likely cause display issues and/or JavaScript errors. It will take a lot of work on your part to come up with the optimal selection and order of files to combine for the site to work properly with a minimum amount of requests made to the server. This process typically takes hours for a simple, small site and days or weeks for a more complex site. It also requires frequent maintenance e.g. when you update Joomla or your extensions as things _may_ break again. If you are not prepared to put in the required time and effort – **STOP NOW**, uninstall this plugin and be content with how your site works. 
 
Don't be unrealistically optimistic about combining every JS and CSS file under the sun (you'll end up with a broken site at best). Add files carefully and progressively. Some files cannot be combined. Always err on the side of caution. Be careful and methodical. Test thoroughly after every small change. Remember that haste is the enemy of quality. If you have a looming deadline maybe don't try to gain the marginal benefits of combining files and focus on shipping a working site instead.

Speaking of which, combining CSS and JS files is the very dead last thing you should do on a site. Premature optimization is not only a waste of time, it's going to introduce hard to debug issues. If something isn't working properly stop and think. Can you reproduce the problem after disabling this plugin? If not, your problem lies with the combination of your site's CSS and JS files, i.e. you screwed up the order of what you are combining or you're trying to combine files which shouldn't be combined. Backtrack to the last known good settings and start working on the site methodically. 

This plugin will _ONLY_ combine the JS and CSS files which are added to Joomla through its document object before the template asks it to compile its head element (through the API or with a jdoc tag). Files which are hardcoded in the template's output, added by manipulating the HTML document or included through JavaScript will NOT be combined. Trying to do so is a very precarious proposition, one I am not interested in pursuing.

The combined files MUST exist on your server and MUST be readable by PHP. Just the fact that a file is served through your web server and your browser can read it IS NOT an indication that _either_ of these conditions is true. If you do not understand the difference between a file being served through a web server and a file being readable through PHP you do not possess the minimum knowledge required to start doing advanced optimization of your site, i.e. you shouldn't be using this plugin just yet. Try to jump the learning curve at your own risk and peril.

A corollary of the above is that dynamically generated scripts / stylesheets can not be combined. If it has `index.php` in its name or otherwise looks like it's going through Joomla or a `.php` script: it cannot be combined. 

A secondary corollary of the above is that using a third party extension, script or template feature which combines, minifies or compresses (GZips) JavaScript and / or CSS files is incompatible with this plugin. Disable all such solutions before trying to use this plugin. Please keep in mind that compressing files in PHP is more than 10x slower than letting your server do that.

Order matters. The files are included in the combined file in the order you declare them in the configuration. Maintaining the correct order of dependencies is your responsibility. If a third party script depends on inline JavaScript executing before it you will not be able to combine it; it'd break your site. Same for CSS depending on inline CSS.