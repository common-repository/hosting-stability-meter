=== Hosting Stability Meter ===
Contributors: znaeff
Tags: speed, benchmark, CPU, hardware, hosting, stability, rating
Stable tag: 1.0.1
Requires at least: 4.6
Tested up to: 5.5
Requires PHP: 5.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.patreon.com/HostingStabilityMeter

Benchmarks stability measuring in time. Detailed interactive graph for hosting performance peaks and dips let you know hosting is good or bad.

== Description ==

Plugin for measuring hosting benchmarks stability in time.
It uses several benchmarks to find out is your hoster stable or not. Like,

1. CPU test
1. Disk test
1. Database test

The plugin stores benchmarks results and displays detailed graph for every benchmark. The graph shows hosting performance peaks and dips.
Many peaks and dips mean low hosting stability. You can use this in hosting support conversations.

The plugin uses this [benchmarks library](https://github.com/hostingstabilitymeter/php-benchmarks-library).
Each benchmark takes no more that 1 second. You fully control benchmarks calls frequency.

The plugin may inform you about performance lacks by e-mail.
Also it may send stability benchmark results to [HostingStabilityMeter.Com](https://HostingStabilityMeter.Com) if you allow. It helps to maintain a rating of hosters.

*Note: [HostingStabilityMeter.Com](https://HostingStabilityMeter.Com) will never publish or give somebody neither your benchmark results nor hardware information.*

== Installation ==

1. Install the plugin as usual in WordPress.
1. Set up speed loss thresholds for each benchmark: CPU, Disk and Database. It's better to do after 5-6 hours of plugin's working.
1. Enjoy!

== Frequently Asked Questions ==

= Why do I need one more hosting benchmarks plugin? =

Because it measures benchmarks stability in time, not just hardware or database speed.

The response time of your site on the hosting should be stable, predictable and should not depend on the load of neighboring sites on the same hosting server or network.
Unstable response times degrade SEO and usability.

You can increase the speed of the site by purchasing additional resources, but even in this case, the stability may remain low.
Therefore, it is important to choose a stable hosting service, this is a guarantee that you will get the desired response time of the site at any time.

Hosting Stability Meter gives you information about stability of hosting services and helps you to choose the best hoster.

== Screenshots ==

1. Performance graphs with tooltips, thresholds and hardware information.
2. Plugin's settings are very simple.
3. E-mail message.

== Changelog ==

= 1.0.1 =
Marked as WP5 5.5 compatibile

= 1.0 =
First version

== Upgrade Notice ==

= 1.0 =
First version
