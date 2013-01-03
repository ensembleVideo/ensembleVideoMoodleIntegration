These plugins are working and tested in Moodle versions 2.3 and 2.4, but no longer work in previous versions.

Since we're using a custom player at Illinois, the filter and 'source' element in the return values of repository's get_listing and search
methods should be modified to be more stock Ensemble before installing for a non-illinois server.

This uses the simpleAPI to query against. We're installing Ensemble 3.4 on our production server 1/10/2013; at that time I'll start coding
against the new API.

There is no upload mechanism yet, since I don't have the new API available to me yet.

My previous thinking on how uploads should work was to override the standard upload repository and filter recognizable video files off to ensmeble.

That creates the complication of having ambiguity between which destination/playlist to publish the video to and which transcode workflow
to route the upload through, and additionally would require alteration to core moodle functions.

My current thinking is to add an upload form to each repository instance if the authenticated moodle user has access to any ensemble workflows
associated with the destintation/playlist, and let users upload new content using the same repository mechanism; then disallowing media
uploads to the moodle server (since a primary motivation for this is to eliminate use of the moodle filesystem as a media server.)

In 2.4, the enable ensemble filter checkbox in the type config is non-functional, and the filter must be enabled from the manage filters menu
under site administration.

I also assume that all ensemble servers have 'app' infixed between the domain name and the rest of the iis-exposed webpages in the filter,
which I believe to be incorrect.