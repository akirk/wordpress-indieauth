=== IndieAuth ===
Contributors: indieweb, pfefferle, dshanske
Tags: IndieAuth, IndieWeb, IndieWebCamp, login
Requires at least: 4.7
Tested up to: 4.9.6
Stable tag: 2.2.0
License: MIT
License URI: http://opensource.org/licenses/MIT
Donate link: https://opencollective.com/indieweb

IndieAuth is a way for doing Web sign-in, where you use your own URL to sign in to other places.

== Description ==

The plugin turns WordPress into an IndieAuth endpoint. This can be used to act as an authentication mechanism for WordPress and its REST API,
as well as an identity mechanism for other sites. It uses the URL from the profile page to identify the blog user or your author url.

You can also install this plugin to enable web sign-in for your site using your domain.

== Installation ==

1. Upload the `indieauth` directory to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That's it

== Frequently Asked Questions ==

= What is IndieAuth? =

[IndieAuth](https://indieauth.net) is a way for doing Web sign-in, where you use your own homepage to sign in to other places. It is built on top of OAuth 2.0,
which is used by many websites.

= What is IndieAuth.com? =

[Indieauth.com](https://indieauth.com) is the reference implementation of the IndieAuth Protocol and available for public use.

= Why IndieAuth? =

IndieAuth was built on ideas and technology from existing proven technologies like OAuth and OpenID but aims at making it easier for users as well as developers. It also decentralises
much of the process so completely separate implementations and services can be used for each part.

IndieAuth was developed as part of the [Indie Web movement](http://indieweb.org/why) to take back control of your online identity.

= How is Web Sign In different from OpenID? =

The goals of OpenID and Web Sign In are similar. Both encourage you to sign in to a website using your own domain name.
However, OpenID has failed to gain wide adoption, at least in part due to the complexities of the protocol.

= How is IndieAuth different from OAuth? =

IndieAuth was built on top of the OAuth 2.0 Framework and differs in that users and clients are represented by URLs.  Clients can verify the identity of
a user and obtain an OAuth 2.0 Bearer token that can be used to access user resources..

= Does this require users to have their own domain name? =

No. You can use your author profile URL to login if you do not have a domain name. However how the Indieauth server authenticates you depends on that server.

= How do I authenticate myself to an Indieauth server? =

That, as mentioned, depends on the server. By default, the built-in IndieAuth server uses the WordPress login.
IndieAuth.com works by linking your website to one or more authentication providers such as Twitter or Github.

You can link your website to these providers add ['rel-me'](https://indieweb.org/rel-me) links to your site, which can be done manually or by installing
the [Indieweb plugin](https://wordpress.org/plugins/indieweb)

By adding Indieauth support, you can log into sites simply by providing your URL.

= What is a token endpoint? =

Once you have proven your identity, the token endpoint issues a token, which applications can use to authenticate as you to your site.
The plugin supports you using an external token endpoint if you want, but by having it built into your WordPress site, it is under your control.

You can revoke local tokens under User->Manage Tokens.


== Changelog ==

= 2.2.0 =
* Major refactor to abstract out and improve token generation code
* Set one cookie with the state instead of multiple cookies.
* Store other parameters as a transient

= 2.1.1 =
* Bug Fix

= 2.1.0 =

* Refactor to change load order
* Textual fix
* Add defaults when core functions not yet enabled
* Rework of the admin-interface

= 2.0.3 =

* Add improved getallheaders polyfill
* Check for missing cookie
* Check for alternate authorization location

= 2.0.2 =

* If using local endpoint verify token locally without making remote call
* Add filters for scope and response so they can be accessed elsewhere
* urlencode state as some encode information into state that was being lost
* Switch from failure to warning message for different domains for redirect
* Hide token endpoint management page if local endpoint not enabled

= 2.0.1 =

* Improve error handling if null endpoint sent through
* Adjust cookie to GMT
* Add whitepace to form

= 2.0.0 =

* Support author profiles in addition to user URLs
* Change token verification method to match current Indieauth specification
* Add support for token verification to act as a WordPress authentication mechanism.
* Add ability to set any token or authorization endpoint
* Add authorization and token endpoint headers to the site
* Discover and use authorization endpoint for provided URL when logging in
* Allow login using URL
* Add built-in token endpoint ( props to @aaronpk for support on this )
* Add built-in authorization endpoint ( props to @aaronpk for support on this )
* Hide option to login with your domain by default
* Option to sign into your domain is now a separate form
* Automatically add trailing slash to user_url

= 1.1.3 =

* update README

= 1.1.2 =

* fixed redirect URL

= 1.1.1 =

* WordPress coding style

= 1.1.0 =

* fixed critical bug

= 1.0.0 =

* initial
