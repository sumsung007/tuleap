{*
 * Tagtip
 *
 * Tag tooltip template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}

<div>
{t domain="gitphp"}tag{/t}: {$tag->GetName()|escape}
<br />
{foreach from=$tag->GetComment() item=line}
{if strncasecmp(trim($line),'-----BEGIN PGP',14) == 0}
<span class="pgpSig">
{/if}
<br />{$line|escape}
{if strncasecmp(trim($line),'-----END PGP',12) == 0}
</span>
{/if}
{/foreach}
</div>
