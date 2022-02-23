#if !MOWNER_INCLUDED
#define MOWNER_INCLUDED

/// Module for control contract owner
///
/// Owner can change owner of contract
///
/// Example of usage module without other control modules:
/// \code{.ligo}
/// #Include "module/MOwner.ligo"
/// type t_storage record [
///     owner: MOwner.t_owner;
///     ...
/// ];
///
/// type t_entrypoint is
/// | ChangeOwner of MOwner.t_owner
/// ...
///
/// function main(const entrypoint: t_entrypoint; var s: t_storage): t_return is
/// case entrypoint of
/// | ChangeOwner(params) -> (cNO_OPERATIONS, block { s.owner:= MOwner.accessChange(params, s.owner); } with s)
/// ...
/// \endcode
module MOwner is {
    
    type t_owner is address;/// Owner of contract

    const cERR_DENIED: string = "MOwner/Denied";/// Error: Access denied

    /// Is the current user owner of contract
    [@inline] function isOwner(const owner: t_owner): bool is owner = Tezos.sender;

    /// Current user must has owner rights
    /// If caller is not owner, return error cERR_DENIED
    function mustOwner(const owner: t_owner): unit is block {
        if isOwner(owner) then skip
        else failwith(cERR_DENIED);
    } with unit;

    /// Change owner of contract with checking access rights
    function accessChange(const newowner: t_owner; var owner: t_owner): t_owner is block {
        mustOwner(owner);
        owner := newowner;
    } with owner;

}
#endif // !MOWNER_INCLUDED
