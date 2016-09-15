# MailRoute blacklisting via email

After using MailRoute on and off for a few years, the major problem I found was that it was a pain blacklisting domains that got through the spam filter. Whilst you can whitelist via an email digest, there is no simple forwarding mechanism to blacklist a domain. I decided to fix that by creating a free gmail account and a free [Context.io](http://context.io) account and then detecting forwarded emails and blacklisting them via the Mailroute API.

Full details are available on my blog at [https://bendodson.com/weblog/2016/09/15/mailroute-blacklisting-via-email/](https://bendodson.com/weblog/2016/09/15/mailroute-blacklisting-via-email/)
