/* Hookup RSSChecker buttons to AJAX post
 */

jQuery(document).ready( function($) {

  $("a.RSSChecker").click( function() {

    $.post(RSSChecker.ajaxurl, {
        action: $(this).attr("id"),
        check: RSSChecker.check
      }, function(data) {
        /* Refresh all values */
        $("span#RSSPending").html(data.pending);
        $("span#RSSLinks").html(data.links);
        $("span#RSSLoad").html(data.load);
        $("span#RSSTime").html(data.time);
        $("span#RSSNext").html(data.next);
        $("span#RSSMessage").html(data.message);
        $("span#RSSDBVer").html(data.dbver);
        $("span#RSSRunning").toggle(data.running);          // display message if running
        $("a#RSSCheckerProcNext").toggle(data.pending > 0); // hide the pending button if the count is zero 
          
      }
    );
    return false;
  });
   
      $("a.RSSCheckerLink").click( function() {
        $("span#RSSLinkChecked").html("");
        $("span#RSSLinkUpdated").html("");
        $("a#RSSLinkDetail").html("");
        $.post(RSSChecker.ajaxurl, {
            action: $(this).attr("id"),
            linkid: $(this).attr("data_link_id"),
            check: RSSChecker.check
          }, function(data) {
            $("span#RSSLinkChecked").html(data.lastchecked);
            $("span#RSSLinkUpdated").html(data.lastupdate);
            $("a#RSSLinkDetail").attr("href",data.lasturl);
            $("a#RSSLinkDetail").html(data.lasttitle);
          }
        );
        return false;
      });
      
    $('a#RSSCheckerRefresh').trigger('click'); // Populate initial valiues
    $('a#RSSCheckerRefreshLink').trigger('click'); // Populate initial valiues      
       
    });

